<?php

namespace app\controllers;

use Yii;
use app\models\Article;
use app\models\ArticleSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\components\HanumanRule;
use app\models\User;

use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseFileHelper;
use yii\helpers\Html;
use yii\helpers\Url;

use app\models\Province;
use app\models\Amphur;
use app\models\District;

use yii\db\Expression;
use app\models\WorkContributor;
use app\models\WorkContributorRole;

/**
 * ArticleController implements the CRUD actions for Article model.
 */
class ArticleController extends Controller
{

public function behaviors()
{
    return [
        'access' => [
            'class' => AccessControl::class,
            'ruleConfig' => [
                'class' => \app\components\HanumanRule::class,
            ],
            'rules' => [
                [
                    'actions' => ['index', 'error'],
                    'allow'   => true,
                    'roles'   => ['?', '@'],
                ],
                // ✅ position 1 researcher + 4 admin
                [
                    'actions' => ['view', 'create', 'update', 'delete', 'add-contributors', 'delete-contributor'],
                    'allow'   => true,
                    'roles'   => [1, 4],
                ],
            ],
        ],
        'verbs' => [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
            ],
        ],
    ];
}


    /**
     * Lists all Article models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $searchModel  = new ArticleSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        if (!Yii::$app->user->isGuest && $ty) {
            $dataProvider->query->andWhere(['org_id' => $ty]);
        }

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    /**
     * Displays a single Article model.
     * @param int $article_id รหัสบทความ
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($article_id)
    {
        $model = $this->findModel($article_id);

        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

        return $this->render('view', [
            'model' => $model,
            'isOwner' => $isOwner,
        ]);
    }


    /**
     * Creates a new Article model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Article();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'article_id' => $model->article_id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Article model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $article_id รหัสบทความ
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($article_id)
    {
        $model = $this->findModel($article_id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'article_id' => $model->article_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Article model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $article_id รหัสบทความ
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($article_id)
    {
        $model = $this->findModel($article_id);

        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

        if (!$isOwner) {
            throw new \yii\web\ForbiddenHttpException('คุณไม่มีสิทธิ์ลบรายการนี้');
        }

        $model->delete();
        return $this->redirect(['index']);
    }


    /**
     * Finds the Article model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $article_id รหัสบทความ
     * @return Article the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($article_id)
    {
        if (($model = Article::findOne(['article_id' => $article_id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

 public function actionAddContributors($article_id)
{
    $article = Article::findOne((int)$article_id);
    if (!$article) throw new NotFoundHttpException('ไม่พบบทความ');

    if (Yii::$app->user->isGuest) {
        throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
    }

    $form = new WorkContributor();
    $form->scenario = 'multi';
    $form->ref_type = 'article';
    $form->ref_id   = (int)$article->article_id;

    if ($form->load(Yii::$app->request->post()) && $form->validate()) {

        $role = $form->role_code_form ?: 'member';
        $startOrder = (int)$form->sort_order;
        $created = 0;

        // ✅ % จากฟอร์ม (ค่าเดียวให้ทั้งชุด) — ว่างได้
        $pct = $form->pct_form;
        $pct = ($pct === '' || $pct === null) ? null : (float)$pct;

        $selected = (array)$form->usernames;
        $selected = array_map('trim', $selected);
        $selected = array_values(array_unique(array_filter($selected, fn($v) => $v !== '')));

        $tx = Yii::$app->db->beginTransaction();
        try {
            $i = 0;
            foreach ($selected as $uname) {
                $row = new WorkContributor();
                $row->ref_type = 'article';
                $row->ref_id   = (int)$article->article_id;
                $row->username = $uname;
                $row->role_code = $role;
                $row->sort_order = $startOrder + $i;
                $row->note = $form->note;

                // ✅ ใส่ % ตามที่กรอก (ไม่กรอกก็ NULL)
                $row->contribution_pct = $pct;

                try {
                    if ($row->save(false)) {
                        $created++;
                        $i++;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            $tx->commit();
            Yii::$app->session->setFlash('success', "เพิ่มผู้ร่วมสำเร็จ {$created} คน");
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('error', 'บันทึกไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    return $this->redirect(['view', 'article_id' => $article->article_id]);
}


    public function actionDeleteContributor($article_id, $wc_id)
    {
        $article = Article::findOne((int)$article_id);
        if (!$article) throw new NotFoundHttpException('ไม่พบบทความ');

        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$article->username);

        // ตามเงื่อนไขคุณ: ปุ่มลบแสดงเฉพาะเจ้าของเรื่อง
        if (!$isOwner) {
            throw new ForbiddenHttpException('ลบได้เฉพาะเจ้าของเรื่อง');
        }

        $row = WorkContributor::findOne((int)$wc_id);
        if ($row && $row->ref_type === 'article' && (int)$row->ref_id === (int)$article->article_id) {
            $row->delete();
            Yii::$app->session->setFlash('success', 'ลบผู้ร่วมแล้ว');
        }

        return $this->redirect(['view', 'article_id' => $article->article_id]);
    }

    // helper: รายการผู้ใช้ให้ Select2 (ปรับ fullname ให้ตรงกับระบบคุณ)
    protected function getAccountUserItems()
    {
        return \app\models\Account::find()
            ->select(["CONCAT(uname,' ',luname) AS text"])
            ->indexBy('username')
            ->orderBy(['uname' => SORT_ASC])
            ->column();
    }
    
    public function actionUpdateContributorPct($article_id, $wc_id)
    {
        $article = Article::findOne((int)$article_id);
        if (!$article) throw new NotFoundHttpException('ไม่พบบทความ');

        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $isOwner = ($me && (string)$me->username === (string)$article->username);
        if (!$isOwner) throw new ForbiddenHttpException('แก้ไขได้เฉพาะเจ้าของเรื่อง');

        $row = WorkContributor::findOne((int)$wc_id);
        if (!$row || $row->ref_type !== 'article' || (int)$row->ref_id !== (int)$article->article_id) {
            throw new NotFoundHttpException('ไม่พบผู้ร่วม');
        }

        $pct = Yii::$app->request->post('pct');
        $pct = ($pct === '' || $pct === null) ? null : (float)$pct;
        if ($pct !== null && ($pct < 0 || $pct > 100)) {
            Yii::$app->session->setFlash('error', 'สัดส่วนต้องอยู่ระหว่าง 0–100');
            return $this->redirect(['view', 'article_id' => $article->article_id]);
        }

        $row->contribution_pct = $pct;
        $row->save(false, ['contribution_pct']);

        Yii::$app->session->setFlash('success', 'อัปเดตสัดส่วนแล้ว');
        return $this->redirect(['view', 'article_id' => $article->article_id]);
    }

}
