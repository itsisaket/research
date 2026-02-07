<?php

namespace app\controllers;

use Yii;
use app\models\Account;
use app\models\AccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

// โมดูลที่ต้องดึงข้อมูล
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
                    [
                        'actions' => ['index', 'error','view', ],
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
     * ✅ actionView: แสดง
     * 1) รายชื่อเรื่องของผู้ใช้ (4 ตาราง)
     * 2) นับจำนวนเรื่อง (4 ตาราง)
     * 3) ข้อมูลผู้ใช้ (model)
     */
 public function actionView($id)
{
    // 3) ข้อมูลผู้ใช้
    $model = $this->findModel($id);
    $username = $model->username;

    // 2) นับจำนวนเรื่อง
    $cntResearch = (int)\app\models\Researchpro::find()
        ->where(['username' => $username])
        ->count();

    $cntArticle = (int)\app\models\Article::find()
        ->where(['username' => $username])
        ->count();

    $cntUtil = (int)\app\models\Utilization::find()
        ->where(['username' => $username])
        ->count();

    $cntService = (int)\app\models\AcademicService::find()
        ->where(['username' => $username])
        ->count();

    // 1) ดึงรายชื่อเรื่องของผู้ใช้ (ล่าสุด 10 รายการ)
    $researchLatest = \app\models\Researchpro::find()
        ->where(['username' => $username])
        ->orderBy(['research_id' => SORT_DESC])
        ->limit(10)
        ->all();

    $articleLatest = \app\models\Article::find()
        ->where(['username' => $username])
        ->orderBy(['article_id' => SORT_DESC])
        ->limit(10)
        ->all();

    $utilLatest = \app\models\Utilization::find()
        ->where(['username' => $username])
        ->orderBy(['util_id' => SORT_DESC])
        ->limit(10)
        ->all();

    $serviceLatest = \app\models\AcademicService::find()
        ->where(['username' => $username])
        ->orderBy(['service_id' => SORT_DESC])
        ->limit(10)
        ->all();

    // ส่งไป view (ให้ตรงกับไฟล์ view ของคุณ)
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
     * ✅ เลือก owner ที่ปลอดภัยที่สุด
     * - ให้ uid/created_by มาก่อน เพื่อกันเคสตารางไม่มี username แล้วเกิด #42S22
     */
    private function ownerCondition($modelClass, $account)
    {
        $m = new $modelClass();

        if ($m->hasAttribute('uid')) {
            return ['uid' => (int)$account->uid];
        }
        if ($m->hasAttribute('created_by')) {
            return ['created_by' => (int)$account->uid];
        }
        if ($m->hasAttribute('username') && !empty($account->username)) {
            return ['username' => $account->username];
        }

        // ไม่รู้จะผูกด้วยอะไร → คืนผลว่าง
        return ['0' => 1];
    }

    /**
     * ✅ PK ของโมเดล (กันเดาชื่อคอลัมน์ผิด)
     */
    private function pkField($modelClass)
    {
        $pk = $modelClass::primaryKey();
        return $pk[0] ?? 'id';
    }
}
