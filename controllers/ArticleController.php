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
use app\models\Publication;
use yii\helpers\ArrayHelper;
use app\models\WorkContributor;
use app\components\ExcelExporter;

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
                    [
                        'actions' => ['export'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                    [
                        'actions' => ['suggest'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => [
                            'view', 'create', 'update', 'delete',
                            'add-contributors', 'delete-contributor', 'update-contributor-pct'
                        ],
                        'allow'   => true,
                        'roles'   => [1, 4],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-contributor' => ['POST'],
                    'update-contributor-pct' => ['POST'],
                ],
            ],
        ];
    }

public function actionIndex()
{
    $session = Yii::$app->session;
    $ty = $session['ty'] ?? null;

    $searchModel  = new ArticleSearch();
    $dataProvider = $searchModel->search($this->request->queryParams);

    if (!Yii::$app->user->isGuest && $ty) {
        $dataProvider->query->andWhere(['a.org_id' => (int)$ty]); // ถ้า query ใช้ alias a
    }

    // ✅ ดึงรายการประเภทฐานที่ controller
        $pubItems = ArrayHelper::map(
            Publication::find()
                ->andWhere(['>', 'publication_type', 0])   // ✅ ตัด 0
                ->orderBy(['publication_name' => SORT_ASC])
                ->all(),
            'publication_type',
            'publication_name'
        );

    // ✅ นับจำนวนผู้เขียนร่วม batch
    $models = $dataProvider->getModels();
    $articleIds = ArrayHelper::getColumn($models, 'article_id');
    $contribCount = [];
    if (!empty($articleIds)) {
        $rows = (new \yii\db\Query())
            ->select(['ref_id', 'cnt' => 'COUNT(*)'])
            ->from('work_contributor')
            ->where(['ref_type' => 'article', 'ref_id' => $articleIds])
            ->groupBy('ref_id')
            ->all();
        foreach ($rows as $r) {
            $contribCount[(int)$r['ref_id']] = (int)$r['cnt'];
        }
    }

    return $this->render('index', [
        'searchModel'  => $searchModel,
        'dataProvider' => $dataProvider,
        'pubItems'     => $pubItems,
        'contribCount' => $contribCount,
    ]);
}

    /**
     * Export ข้อมูลการตีพิมพ์เผยแพร่ (ตาม filter ปัจจุบัน) เป็นไฟล์ Excel (.xlsx)
     */
    public function actionExport()
    {
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $searchModel  = new ArticleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (!Yii::$app->user->isGuest && $ty) {
            $dataProvider->query->andWhere(['a.org_id' => (int)$ty]);
        }

        // ดึงผู้เขียนร่วมแบบ batch
        $dataProvider->pagination = false;
        $models   = $dataProvider->getModels();
        $refIds   = ArrayHelper::getColumn($models, 'article_id');
        $contribs = ExcelExporter::fetchContributorsMap('article', $refIds);

        $columns = [
            ['header' => 'ลำดับ', 'value' => function ($m, $i) { return $i + 1; }, 'format' => 'number'],
            ['header' => 'รหัสบทความ', 'value' => 'article_id'],
            ['header' => 'ชื่อบทความ (ไทย)', 'value' => 'article_th'],
            ['header' => 'ชื่อบทความ (อังกฤษ)', 'value' => 'article_eng'],
            ['header' => 'ประเภทฐาน', 'value' => function ($m) {
                return $m->publi->publication_name ?? '';
            }],
            ['header' => 'วารสาร/งานประชุม', 'value' => 'journal'],
            ['header' => 'วันที่เผยแพร่', 'value' => function ($m) {
                return ExcelExporter::formatThaiDate($m->article_publish);
            }],
            ['header' => 'หน่วยงาน', 'value' => function ($m) {
                return $m->hasorg->org_name ?? '';
            }],
            ['header' => 'ผู้บันทึก/เจ้าของเรื่อง', 'value' => function ($m) {
                if (!$m->user) return '';
                return trim(($m->user->uname ?? '') . ' ' . ($m->user->luname ?? ''));
            }],
            ['header' => 'ผู้เขียนร่วม (ทุกคน + บทบาท + %)', 'value' => function ($m) use ($contribs) {
                return $contribs[(int)$m->article_id] ?? '';
            }],
            ['header' => 'สาขาวิชา', 'value' => function ($m) {
                return $m->habranch->branch_name ?? '';
            }],
            ['header' => 'จริยธรรมในมนุษย์', 'value' => function ($m) {
                return $m->haec->ec_name ?? '';
            }],
            ['header' => 'อ้างอิง', 'value' => 'refer'],
        ];

        return ExcelExporter::export($dataProvider, $columns, [
            'filename'   => 'article_' . date('Ymd_His'),
            'sheetTitle' => 'การตีพิมพ์เผยแพร่',
            'title'      => 'รายการการตีพิมพ์เผยแพร่',
            'subtitle'   => 'พิมพ์เมื่อ ' . ExcelExporter::formatThaiDate(date('Y-m-d')),
        ]);
    }

    /**
     * Autocomplete suggestions สำหรับ quick search
     */
    public function actionSuggest($q = '')
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $q = trim((string)$q);
        if (mb_strlen($q) < 2) {
            return ['items' => []];
        }

        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $query = Article::find()->alias('a')
            ->leftJoin('tb_user u', 'u.username = a.username')
            ->select([
                'a.article_id',
                'a.article_th',
                'a.journal',
                'a.article_publish',
                'u.uname',
                'u.luname',
            ])
            ->andWhere(['or',
                ['like', 'a.article_th',  $q],
                ['like', 'a.article_eng', $q],
                ['like', 'a.journal',     $q],
                ['like', 'a.refer',       $q],
                ['like', 'u.uname',       $q],
                ['like', 'u.luname',      $q],
            ])
            ->orderBy(['a.article_id' => SORT_DESC])
            ->limit(8)
            ->asArray();

        if (!Yii::$app->user->isGuest && $ty) {
            $query->andWhere(['a.org_id' => (int)$ty]);
        }

        $rows = $query->all();
        $items = [];
        foreach ($rows as $r) {
            $name = trim(((string)($r['uname'] ?? '')) . ' ' . ((string)($r['luname'] ?? '')));
            $sub = trim($name . ($r['journal'] ? ' • ' . $r['journal'] : ''));
            $items[] = [
                'id'       => (int)$r['article_id'],
                'title'    => (string)$r['article_th'],
                'subtitle' => $sub,
                'url'      => \yii\helpers\Url::to(['view', 'article_id' => $r['article_id']]),
            ];
        }

        return ['items' => $items];
    }

    public function actionView($article_id)
    {
        $model = $this->findModel($article_id);

        $me = Yii::$app->user->identity ?? null;
        $isOwner = ($me && (string)$me->username === (string)$model->username);

        return $this->render('view', [
            'model' => $model,
            'isOwner' => $isOwner,
        ]);
        
    }

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

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($article_id)
    {
        $model = $this->findModel($article_id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'article_id' => $model->article_id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($article_id)
    {
        $model = $this->findModel($article_id);

        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

        if (!$isOwner) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ลบรายการนี้');
        }

        $model->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($article_id)
    {
        if (($model = Article::findOne(['article_id' => $article_id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
