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

    // ✅ ใช้ username จาก account เท่านั้น
    $username = $model->username;

    // ===== งานวิจัย =====
    $cntResearch = Researchpro::find()
        ->where(['username' => $username])
        ->count();

    $researchLatest = Researchpro::find()
        ->where(['username' => $username])
        ->orderBy(['research_id' => SORT_DESC])
        ->limit(10)
        ->all();

    // ===== บทความ =====
    $cntArticle = Article::find()
        ->where(['username' => $username])
        ->count();

    $articleLatest = Article::find()
        ->where(['username' => $username])
        ->orderBy(['article_id' => SORT_DESC])
        ->limit(10)
        ->all();

    // ===== การนำไปใช้ =====
    $cntUtil = Utilization::find()
        ->where(['username' => $username])
        ->count();

    $utilLatest = Utilization::find()
        ->where(['username' => $username])
        ->orderBy(['util_id' => SORT_DESC])
        ->limit(10)
        ->all();

    // ===== บริการวิชาการ =====
    $cntService = AcademicService::find()
        ->where(['username' => $username])
        ->count();

    $serviceLatest = AcademicService::find()
        ->where(['username' => $username])
        ->orderBy(['service_id' => SORT_DESC])
        ->limit(10)
        ->all();

    return $this->render('view', [
        'model' => $model,

        'cntResearch' => (int)$cntResearch,
        'cntArticle'  => (int)$cntArticle,
        'cntUtil'     => (int)$cntUtil,
        'cntService'  => (int)$cntService,

        'researchLatest' => $researchLatest,
        'articleLatest'  => $articleLatest,
        'utilLatest'     => $utilLatest,
        'serviceLatest'  => $serviceLatest,
    ]);
}



}
