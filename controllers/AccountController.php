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
    $model = $this->findModel($id);

    // ===== งานวิจัย =====
    $condResearch = $this->ownerCondition(Researchpro::class, $model);
    $cntResearch = (int)Researchpro::find()->where($condResearch)->count();
    $researchLatest = Researchpro::find()
        ->where($condResearch)
        ->orderBy(['research_id' => SORT_DESC])
        ->limit(10)
        ->all();

    // ===== บทความ =====
    $condArticle = $this->ownerCondition(Article::class, $model);
    $cntArticle = (int)Article::find()->where($condArticle)->count();
    $articleLatest = Article::find()
        ->where($condArticle)
        ->orderBy(['article_id' => SORT_DESC])
        ->limit(10)
        ->all();

    // ===== การนำไปใช้ =====
    $condUtil = $this->ownerCondition(Utilization::class, $model);
    $cntUtil = (int)Utilization::find()->where($condUtil)->count();
    $utilLatest = Utilization::find()
        ->where($condUtil)
        ->orderBy(['util_id' => SORT_DESC])
        ->limit(10)
        ->all();

    // ===== บริการวิชาการ =====
    $condService = $this->ownerCondition(AcademicService::class, $model);
    $cntService = (int)AcademicService::find()->where($condService)->count();
    $serviceLatest = AcademicService::find()
        ->where($condService)
        ->orderBy(['service_id' => SORT_DESC])
        ->limit(10)
        ->all();

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

    // ❌ ห้ามใช้ ['0'=>1]
    // ✅ ใช้แบบนี้แทน
    return '1=0';
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
