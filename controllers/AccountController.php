<?php

namespace app\controllers;

use Yii;
use app\models\Account;
use app\models\AccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use app\models\Researchpro;
use app\models\Article;
use app\models\Utilization;
use app\models\AcademicService;
use app\models\WorkContributor;

class AccountController extends Controller
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
                        'actions' => ['index', 'error', 'view'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => ['resetpassword'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                    [
                        'actions' => ['create', 'update', 'delete'],
                        'allow'   => true,
                        'roles'   => ['@'],
                        'matchCallback' => function () {
                            $u = Yii::$app->user->identity;
                            return ($u instanceof \app\models\Account)
                                && in_array((int)$u->position, [1, 4], true);
                        },
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

        $searchModel = new AccountSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (!empty($ty)) {
            $dataProvider->query->andWhere(['org_id' => $ty]);
        }

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 1) รายชื่อเรื่องของผู้ใช้ (4 ตาราง)
     * 2) นับจำนวนเรื่อง (4 ตาราง)
     * 3) ข้อมูลผู้ใช้ (model)
     * ✅ ค้นจาก username เท่านั้น
     */
public function actionView($id)
{
    $model = $this->findModel($id);
    $username = (string)$model->username;

    /* ================= งานเจ้าของ (ของเดิม) ================= */

    // งานวิจัย
    $cntResearch = (int)Researchpro::find()->where(['username' => $username])->count();
    $researchLatest = Researchpro::find()
        ->where(['username' => $username])
        ->orderBy([Researchpro::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // บทความ
    $cntArticle = (int)Article::find()->where(['username' => $username])->count();
    $articleLatest = Article::find()
        ->where(['username' => $username])
        ->orderBy([Article::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // การนำไปใช้
    $cntUtil = (int)Utilization::find()->where(['username' => $username])->count();
    $utilLatest = Utilization::find()
        ->where(['username' => $username])
        ->orderBy([Utilization::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // บริการวิชาการ
    $cntService = (int)AcademicService::find()->where(['username' => $username])->count();
    $serviceLatest = AcademicService::find()
        ->where(['username' => $username])
        ->orderBy([AcademicService::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    /* ================= ผู้ร่วมงาน (ใหม่) ================= */

    // ดึงรายการที่ user เป็น contributor
    $contributors = WorkContributor::find()
        ->where(['username' => $username])
        ->orderBy(['ref_type' => SORT_ASC, 'sort_order' => SORT_ASC])
        ->all();

    // แยกตามประเภทงาน (ง่ายต่อ view)
    $contribResearch = [];
    $contribArticle  = [];
    $contribUtil     = [];

    foreach ($contributors as $wc) {
        switch ($wc->ref_type) {
            case 'researchpro':
                if ($m = Researchpro::findOne($wc->ref_id)) {
                    $contribResearch[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'article':
                if ($m = Article::findOne($wc->ref_id)) {
                    $contribArticle[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'utilization':
                if ($m = Utilization::findOne($wc->ref_id)) {
                    $contribUtil[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;
        }
    }

    return $this->render('view', compact(
        'model',
        'cntResearch','cntArticle','cntUtil','cntService',
        'researchLatest','articleLatest','utilLatest','serviceLatest',
        // 👇 ส่งข้อมูลผู้ร่วมไป view
        'contribResearch','contribArticle','contribUtil'
    ));
}


protected function findModel($id)
{
    if (($model = Account::findOne(['uid' => (int)$id])) !== null) {
        return $model;
    }
    throw new NotFoundHttpException('ไม่พบข้อมูลผู้ใช้ที่ต้องการ');
}
}
