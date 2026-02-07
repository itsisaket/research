<?php

namespace app\controllers;

use Yii;
use app\models\Account;
use app\models\AccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

// ✅ ปรับชื่อโมเดลตามโปรเจกต์คุณ
use app\models\Researchpro;
use app\models\Article;
use app\models\Utilization;
use app\models\AcademicService;

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
                    // ✅ หน้า index ให้ guest/สมาชิกเข้าได้ (ตามที่คุณทำเดิม)
                    [
                        'actions' => ['index', 'error'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],

                    // ✅ view ให้ "คนล็อกอิน" เข้าได้ โดยไม่พึ่ง roles เป็นตัวเลข (กัน 403 #2)
                    [
                        'actions' => ['view', 'resetpassword'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],

                    // ✅ create/update/delete เฉพาะ position 1,4 (admin/ผู้ดูแล)
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

public function actionView($id)
{
    $model = $this->findModel($id);
    $username = $model->username;

    /* =========================
     * 1) นับจำนวนเรื่อง
     * ========================= */
    $cntResearch = (int)\app\models\Researchpro::find()
        ->where(['username' => $username])->count();

    $cntArticle = (int)\app\models\Article::find()
        ->where(['username' => $username])->count();

    $cntUtil = (int)\app\models\Utilization::find()
        ->where(['username' => $username])->count();

    $cntService = (int)\app\models\AcademicService::find()
        ->where(['username' => $username])->count();

    /* =========================
     * 2) ดึงรายชื่อเรื่อง (ล่าสุด 10)
     * ========================= */
    $researchLatest = \app\models\Researchpro::find()
        ->select(['research_id','projectNameTH'])
        ->where(['username' => $username])
        ->orderBy(['research_id' => SORT_DESC])
        ->limit(10)
        ->all();

    $articleLatest = \app\models\Article::find()
        ->select(['article_id','article_th'])
        ->where(['username' => $username])
        ->orderBy(['article_id' => SORT_DESC])
        ->limit(10)
        ->all();

    $utilLatest = \app\models\Utilization::find()
        ->select(['util_id','project_name'])
        ->where(['username' => $username])
        ->orderBy(['util_id' => SORT_DESC])
        ->limit(10)
        ->all();

    $serviceLatest = \app\models\AcademicService::find()
        ->select(['service_id','title'])
        ->where(['username' => $username])
        ->orderBy(['service_id' => SORT_DESC])
        ->limit(10)
        ->all();

    /* =========================
     * 3) ส่งไป view
     * ========================= */
    return $this->render('view', [
        'model' => $model,

        'cntResearch' => $cntResearch,
        'cntArticle'  => $cntArticle,
        'cntUtil'     => $cntUtil,
        'cntService'  => $cntService,

        'researchLatest' => $researchLatest,
        'articleLatest'  => $articleLatest,
        'utilLatest'     => $utilLatest,
        'serviceLatest'  => $serviceLatest,
    ]);
}


    protected function findModel($id)
    {
        if (($model = Account::findOne((int)$id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('ไม่พบข้อมูลผู้ใช้ที่ต้องการ');
    }

    /**
     * ✅ เลือกเงื่อนไข owner โดยดูจากคอลัมน์ที่ "มีจริง" ของแต่ละตาราง
     * - รองรับ: username / uid / created_by
     */
    private function ownerCondition($modelClass, $account)
    {
        $m = new $modelClass();

        if ($m->hasAttribute('username') && !empty($account->username)) {
            return ['username' => $account->username];
        }
        if ($m->hasAttribute('uid')) {
            return ['uid' => (int)$account->uid];
        }
        if ($m->hasAttribute('created_by')) {
            return ['created_by' => (int)$account->uid];
        }

        // ไม่รู้จะผูกด้วยอะไร → กัน error (คืนผลว่าง)
        return ['0' => 1];
    }

    /**
     * ✅ หา PK field ของโมเดล เพื่อ orderBy ได้ถูกต้อง ไม่เดาคอลัมน์
     */
    private function pkField($modelClass)
    {
        $pk = $modelClass::primaryKey();
        return $pk[0] ?? 'id';
    }
}
