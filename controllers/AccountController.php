<?php

namespace app\controllers;

use Yii;
use app\models\Account;
use app\models\AccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;;

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

    public function actionSyncProfile($id)
    {
        $model = Account::findOne($id);
        if (!$model) throw new NotFoundHttpException('ไม่พบผู้ใช้');

        if ($model->syncProfileFromAuthen()) {
            // save เฉพาะฟิลด์ที่ sync (กันกระทบ validation อื่น)
            $ok = $model->save(false, ['academic_type_name','first_name','last_name','dept_name','faculty_name']);
            Yii::$app->session->setFlash($ok ? 'success' : 'error', $ok ? 'Sync สำเร็จ' : 'Sync แล้วแต่บันทึกไม่สำเร็จ');
        } else {
            Yii::$app->session->setFlash('warning', 'ไม่พบข้อมูลจาก API หรือ personal_id ว่าง');
        }

        return $this->redirect(['index']);
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

        // ✅ username = personal_id
        $models = $dataProvider->getModels();
        $ids = [];
        foreach ($models as $m) {
            if (!empty($m->username)) $ids[] = (string)$m->username;
        }

        $profileMap = Yii::$app->sciProfile->getMap($ids);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'profileMap'   => $profileMap,
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

    /* =========================================================
     * 1) Latest (เจ้าของ) — เหมือนเดิม
     * ========================================================= */

    // งานวิจัย (เจ้าของล่าสุด)
    $researchLatest = Researchpro::find()
        ->where(['username' => $username])
        ->orderBy([Researchpro::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // บทความ (เจ้าของล่าสุด)
    $articleLatest = Article::find()
        ->where(['username' => $username])
        ->orderBy([Article::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // การนำไปใช้ (เจ้าของล่าสุด)
    $utilLatest = Utilization::find()
        ->where(['username' => $username])
        ->orderBy([Utilization::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // บริการวิชาการ (เจ้าของล่าสุด)
    $serviceLatest = AcademicService::find()
        ->where(['username' => $username])
        ->orderBy([AcademicService::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    /* =========================================================
     * 2) KPI รวม (เจ้าของ + ผู้ร่วม) แบบกันซ้ำ
     * ========================================================= */

    // --- งานวิจัย ---
    $researchPk = Researchpro::primaryKey()[0];

    $ownResearchIds = Researchpro::find()
        ->select($researchPk)
        ->where(['username' => $username]);

    $contribResearchIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username'  => $username,
            'ref_type'  => 'researchpro',
        ]);

    $cntResearch = (int)Researchpro::find()
        ->where(['or',
            ['in', $researchPk, $ownResearchIds],
            ['in', $researchPk, $contribResearchIds],
        ])
        ->distinct()
        ->count();


    // --- บทความ ---
    $articlePk = Article::primaryKey()[0];

    $ownArticleIds = Article::find()
        ->select($articlePk)
        ->where(['username' => $username]);

    $contribArticleIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username' => $username,
            'ref_type' => 'article',
        ]);

    $cntArticle = (int)Article::find()
        ->where(['or',
            ['in', $articlePk, $ownArticleIds],
            ['in', $articlePk, $contribArticleIds],
        ])
        ->distinct()
        ->count();


    // --- การนำไปใช้ ---
    $utilPk = Utilization::primaryKey()[0];

    $ownUtilIds = Utilization::find()
        ->select($utilPk)
        ->where(['username' => $username]);

    $contribUtilIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username' => $username,
            'ref_type' => 'utilization',
        ]);

    $cntUtil = (int)Utilization::find()
        ->where(['or',
            ['in', $utilPk, $ownUtilIds],
            ['in', $utilPk, $contribUtilIds],
        ])
        ->distinct()
        ->count();


    // --- บริการวิชาการ ---
    $servicePk = AcademicService::primaryKey()[0];

    $ownServiceIds = AcademicService::find()
        ->select($servicePk)
        ->where(['username' => $username]);

    $contribServiceIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username' => $username,
            'ref_type' => 'academic_service', // ✅ ต้องให้ตรงกับ ref_type ที่คุณใช้จริง
        ]);

    $cntService = (int)AcademicService::find()
        ->where(['or',
            ['in', $servicePk, $ownServiceIds],
            ['in', $servicePk, $contribServiceIds],
        ])
        ->distinct()
        ->count();


    /* =========================================================
     * 3) ดึงรายการผู้ร่วม (ไว้แสดงใน view) — เหมือนเดิม
     * ========================================================= */

    $contributors = WorkContributor::find()
        ->where(['username' => $username])
        ->orderBy(['ref_type' => SORT_ASC, 'sort_order' => SORT_ASC])
        ->all();

    $contribResearch = [];
    $contribArticle  = [];
    $contribUtil     = [];
    $contribService  = [];

    foreach ($contributors as $wc) {
        switch ($wc->ref_type) {

            case 'researchpro':
                if ($m = Researchpro::findOne((int)$wc->ref_id)) {
                    $contribResearch[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'article':
                if ($m = Article::findOne((int)$wc->ref_id)) {
                    $contribArticle[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'utilization':
                if ($m = Utilization::findOne((int)$wc->ref_id)) {
                    $contribUtil[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'academic_service':
                if ($m = AcademicService::findOne((int)$wc->ref_id)) {
                    $contribService[] = [
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
        'contribResearch','contribArticle','contribUtil','contribService'
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
