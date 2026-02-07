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

   // ✅ actionView ใหม่ (สมบูรณ์ + ปลอดภัยทั้ง owner/PK + ไม่ใช้ $username ใน view)
public function actionView($id)
{
    $model = $this->findModel($id);

    // ✅ 4 โมดูลที่จะสรุป
    $classes = [
        'research' => Researchpro::class,
        'article'  => Article::class,
        'util'     => Utilization::class,
        'service'  => AcademicService::class,
    ];

    // ✅ map route หน้า index/view ของแต่ละโมดูล (แก้ชื่อ route ให้ตรงระบบคุณถ้าต่าง)
    $routes = [
        'research' => ['index' => '/researchpro/index',     'view' => '/researchpro/view',     'search' => 'ResearchproSearch'],
        'article'  => ['index' => '/article/index',        'view' => '/article/view',         'search' => 'ArticleSearch'],
        'util'     => ['index' => '/utilization/index',    'view' => '/utilization/view',     'search' => 'UtilizationSearch'],
        'service'  => ['index' => '/academic-service/index','view'=> '/academic-service/view', 'search' => 'AcademicServiceSearch'],
    ];

    $cnt = [];
    $latest = [];
    $indexUrl = [];

    foreach ($classes as $k => $cls) {
        // ✅ เงื่อนไขเจ้าของ (ดูจากคอลัมน์ที่มีจริง)
        $cond = $this->ownerCondition($cls, $model);

        // ✅ pk ที่มีจริง
        $pk = $this->pkField($cls);

        // ✅ count + latest
        $cnt[$k] = (int)$cls::find()->where($cond)->count();
        $latest[$k] = $cls::find()->where($cond)->orderBy([$pk => SORT_DESC])->limit(5)->all();

        // ✅ สร้าง url ไปหน้า index พร้อม filter อัตโนมัติ
        // แปลง $cond (เช่น ['uid'=>1]) ให้เป็น param ของ SearchModel (เช่น ResearchproSearch[uid]=1)
        $searchName = $routes[$k]['search'] ?? null;
        $params = [$routes[$k]['index']];

        if ($searchName && is_array($cond)) {
            foreach ($cond as $field => $value) {
                // เงื่อนไขกัน error (เช่น ['0'=>1]) ไม่ส่งไป
                if ($field === '0') continue;
                $params[$searchName . '[' . $field . ']'] = $value;
            }
        }

        $indexUrl[$k] = $params;
    }

    return $this->render('view', [
        'model' => $model,

        // counts
        'cntResearch' => $cnt['research'] ?? 0,
        'cntArticle'  => $cnt['article'] ?? 0,
        'cntUtil'     => $cnt['util'] ?? 0,
        'cntService'  => $cnt['service'] ?? 0,

        // latest lists
        'researchLatest' => $latest['research'] ?? [],
        'articleLatest'  => $latest['article'] ?? [],
        'utilLatest'     => $latest['util'] ?? [],
        'serviceLatest'  => $latest['service'] ?? [],

        // ✅ ส่ง url ไปหน้า index ที่ filter แล้ว (view ใช้ทำปุ่ม “ดูทั้งหมด”)
        'indexUrl' => $indexUrl,

        // ✅ ส่ง route view เผื่อ view จะใช้
        'viewRoute' => [
            'research' => $routes['research']['view'],
            'article'  => $routes['article']['view'],
            'util'     => $routes['util']['view'],
            'service'  => $routes['service']['view'],
        ],
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
