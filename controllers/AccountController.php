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
                        'actions' => ['index', 'error'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => ['view', 'resetpassword'],
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

        // กำหนดโมดูลที่จะดึง
        $modules = [
            'research' => [
                'class' => Researchpro::class,
                'titleField' => 'projectNameTH', // ชื่อโครงการภาษาไทย
            ],
            'article' => [
                'class' => Article::class,
                'titleField' => 'article_th',    // ชื่อบทความ(ไทย)
            ],
            'util' => [
                'class' => Utilization::class,
                'titleField' => 'project_name',  // โครงการวิจัย/งานสร้างสรรค์
            ],
            'service' => [
                'class' => AcademicService::class,
                'titleField' => 'title',         // เรื่อง
            ],
        ];

        $counts = [];
        $latest = [];

        foreach ($modules as $key => $cfg) {
            $cls = $cfg['class'];

            // ✅ owner condition ปลอดภัย (uid/created_by/username ตามที่มีจริง)
            $cond = $this->ownerCondition($cls, $model);

            // ✅ pk ของตาราง
            $pk = $this->pkField($cls);

            // ✅ นับจำนวน
            $counts[$key] = (int)$cls::find()->where($cond)->count();

            // ✅ ดึงรายการล่าสุด 10 (ต้องมีฟิลด์ชื่อเรื่องใน view)
            // ไม่ select เฉพาะคอลัมน์ เพื่อกันปัญหา AR ไม่ครบ/relations (ปลอดภัยสุด)
            $latest[$key] = $cls::find()
                ->where($cond)
                ->orderBy([$pk => SORT_DESC])
                ->limit(10)
                ->all();
        }

        return $this->render('view', [
            'model' => $model,

            // counts
            'cntResearch' => $counts['research'] ?? 0,
            'cntArticle'  => $counts['article'] ?? 0,
            'cntUtil'     => $counts['util'] ?? 0,
            'cntService'  => $counts['service'] ?? 0,

            // latest lists
            'researchLatest' => $latest['research'] ?? [],
            'articleLatest'  => $latest['article'] ?? [],
            'utilLatest'     => $latest['util'] ?? [],
            'serviceLatest'  => $latest['service'] ?? [],
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
