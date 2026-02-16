<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

use app\models\WorkContributor;
use app\models\Utilization;

use app\models\Researchpro;
use app\models\Account;
use app\models\Organize;
use app\models\Restype;
use app\models\Resstatus;
use app\models\ResFund;
use app\models\ResGency;

use app\models\Article;
use app\models\AcademicService;

class ReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // ✅ เปิดให้ทุกคนเรียกได้ แต่คุมด้วย HMAC ใน actionLascApi()
                'rules' => [
                    [
                        'actions' => ['index', 'lasc-api'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'lasc-api' => ['GET'],
                    'delete' => ['POST'],
                    'delete-contributor' => ['POST'],
                    'update-contributor-pct' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $user        = Yii::$app->user->identity;
        $session     = Yii::$app->session;
        $sessionOrg  = $session['ty'] ?? null;
        $isSelfRole  = false;

        // position 1,2 เห็นเฉพาะของตัวเอง
        if ($user && ((int)$user->position === 1 || (int)$user->position === 2)) {
            $isSelfRole = true;
        }

        /* =========================================================
         * 1) กราฟ 5 ปีย้อนหลัง (จำนวนโครงการ + งบประมาณรายปี)
         * ========================================================= */
        $seriesY        = [];
        $budgetSeriesY  = [];
        $categoriesY    = [];

        $currentYearAD = (int) date('Y');
        $currentYearTH = $currentYearAD + 543;

        $yearsTH = [];
        for ($i = 0; $i < 5; $i++) {
            $yearsTH[] = $currentYearTH - $i;
        }
        $yearsTH = array_reverse($yearsTH);

        foreach ($yearsTH as $yearTH) {
            $q = Researchpro::find()->where(['projectYearsubmit' => $yearTH]);

            if ($isSelfRole) {
                $q->andWhere(['username' => $user->username]);
            } else {
                if ($user && (int)$user->position !== 4) {
                    if (!empty($sessionOrg)) {
                        $q->andWhere(['org_id' => $sessionOrg]);
                    } elseif (!empty($user->org_id)) {
                        $q->andWhere(['org_id' => $user->org_id]);
                    }
                }
            }

            $countProject  = (int) (clone $q)->count();
            $sumBudgetYear = (int) (clone $q)->sum('budgets');

            $seriesY[]       = $countProject;
            $budgetSeriesY[] = $sumBudgetYear;
            $categoriesY[]   = (string) $yearTH;
        }

        /* =========================================================
         * 2) กราฟแยกตามหน่วยงาน (Organize)
         * ========================================================= */
        $seriesO     = [];
        $categoriesO = [];

        $orgQuery = Organize::find()->orderBy(['org_id' => SORT_ASC]);
        if ($user && (int)$user->position !== 4 && !empty($sessionOrg)) {
            $orgQuery->andWhere(['org_id' => $sessionOrg]);
        }
        $orgs = $orgQuery->all();

        foreach ($orgs as $org) {
            $oq = Researchpro::find()->where(['org_id' => $org->org_id]);
            if ($isSelfRole) {
                $oq->andWhere(['username' => $user->username]);
            }
            $seriesO[]     = (int) $oq->count();
            $categoriesO[] = $org->org_name;
        }

        /* =========================================================
         * 3) กล่องสรุปบน (วิจัย/บทความ/แผนงาน/บริการ)
         * ========================================================= */
        if ($isSelfRole) {
            $username = $user->username;

            $counttype1 = Researchpro::find()->where(['username' => $username, 'researchTypeID' => 1])->count();
            $counttype2 = Researchpro::find()->where(['username' => $username, 'researchTypeID' => 2])->count();
            $counttype3 = AcademicService::find()->where(['username' => $username])->count();
            $counttype4 = Article::find()->where(['username' => $username])->count();

            $countuser  = trim($user->uname . ' ' . $user->luname);
        } else {
            $counttype1 = Researchpro::find()->where(['researchTypeID' => 1])->count();
            $counttype2 = Researchpro::find()->where(['researchTypeID' => 2])->count();
            $counttype3 = AcademicService::find()->count();
            $counttype4 = Article::find()->count();

            $countuser  = Account::find()->count();
        }

        /* =========================================================
         * 4) สรุป 5 ประเด็นหลัก (รวมทุกปีที่มองเห็น)
         * ========================================================= */
        $baseQuery = Researchpro::find();
        if ($isSelfRole) {
            $baseQuery->andWhere(['username' => $user->username]);
        } else {
            if ($user && (int)$user->position !== 4) {
                if (!empty($sessionOrg)) {
                    $baseQuery->andWhere(['org_id' => $sessionOrg]);
                } elseif (!empty($user->org_id)) {
                    $baseQuery->andWhere(['org_id' => $user->org_id]);
                }
            }
        }

        $totalBudgets = (int) (clone $baseQuery)->sum('budgets');

        $typeData = [];
        $typeRows = (clone $baseQuery)
            ->select(['researchTypeID', 'cnt' => 'COUNT(*)'])
            ->groupBy('researchTypeID')
            ->orderBy('researchTypeID')
            ->asArray()
            ->all();
        foreach ($typeRows as $row) {
            $typeData[$row['researchTypeID']] = (int) $row['cnt'];
        }

        $fundData = [];
        $fundRows = (clone $baseQuery)
            ->select(['researchFundID', 'cnt' => 'COUNT(*)'])
            ->groupBy('researchFundID')
            ->orderBy('researchFundID')
            ->asArray()
            ->all();
        foreach ($fundRows as $row) {
            $fundData[$row['researchFundID']] = (int) $row['cnt'];
        }

        $statusData = [];
        $statusRows = (clone $baseQuery)
            ->select(['jobStatusID', 'cnt' => 'COUNT(*)'])
            ->groupBy('jobStatusID')
            ->orderBy('jobStatusID')
            ->asArray()
            ->all();
        foreach ($statusRows as $row) {
            $statusData[$row['jobStatusID']] = (int) $row['cnt'];
        }

        $agencyData = [];
        $agencyRows = (clone $baseQuery)
            ->select(['fundingAgencyID', 'cnt' => 'COUNT(*)'])
            ->groupBy('fundingAgencyID')
            ->orderBy('fundingAgencyID')
            ->asArray()
            ->all();
        foreach ($agencyRows as $row) {
            $agencyData[$row['fundingAgencyID']] = (int) $row['cnt'];
        }

        /* =========================================================
         * 5) แหล่งทุนรายปี (เฉพาะที่มีโครงการจริงในช่วง 5 ปี)
         * ========================================================= */
        $agencyMap = ResGency::find()
            ->select(['fundingAgencyID', 'fundingAgencyName'])
            ->indexBy('fundingAgencyID')
            ->asArray()
            ->all();

        $fundingSeries       = [];
        $fundingTotalNonZero = [];

        $candidateAgencyIds = array_keys($agencyData);

        foreach ($candidateAgencyIds as $agencyId) {
            $dataPerYear     = [];
            $totalThisAgency = 0;

            foreach ($yearsTH as $yearTH) {
                $aq = Researchpro::find()->where([
                    'projectYearsubmit' => $yearTH,
                    'fundingAgencyID'   => $agencyId,
                ]);

                if ($isSelfRole) {
                    $aq->andWhere(['username' => $user->username]);
                } else {
                    if ($user && (int)$user->position !== 4) {
                        if (!empty($sessionOrg)) {
                            $aq->andWhere(['org_id' => $sessionOrg]);
                        } elseif (!empty($user->org_id)) {
                            $aq->andWhere(['org_id' => $user->org_id]);
                        }
                    }
                }

                $c = (int) $aq->count();
                $dataPerYear[] = $c;
                $totalThisAgency += $c;
            }

            if ($totalThisAgency > 0) {
                $fundingSeries[] = [
                    'name' => $agencyMap[$agencyId]['fundingAgencyName'] ?? ('แหล่งทุน ' . $agencyId),
                    'data' => $dataPerYear,
                ];

                $fundingTotalNonZero[] = [
                    'id'    => $agencyId,
                    'name'  => $agencyMap[$agencyId]['fundingAgencyName'] ?? ('แหล่งทุน ' . $agencyId),
                    'total' => $totalThisAgency,
                ];
            }
        }

        $restypeMap   = Restype::find()->select(['restypeid', 'restypename'])->indexBy('restypeid')->asArray()->all();
        $resfundMap   = ResFund::find()->select(['researchFundID', 'researchFundName'])->indexBy('researchFundID')->asArray()->all();
        $resstatusMap = Resstatus::find()->select(['statusid', 'statusname'])->indexBy('statusid')->asArray()->all();

        return $this->render('index', [
            'seriesY'        => $seriesY,
            'budgetSeriesY'  => $budgetSeriesY,
            'categoriesY'    => $categoriesY,

            'seriesO'        => $seriesO,
            'categoriesO'    => $categoriesO,

            'counttype1'     => $counttype1,
            'counttype2'     => $counttype2,
            'counttype3'     => $counttype3,
            'counttype4'     => $counttype4,
            'countuser'      => $countuser,

            'isSelfRole'     => $isSelfRole,

            'totalBudgets'   => $totalBudgets,
            'typeData'       => $typeData,
            'fundData'       => $fundData,
            'statusData'     => $statusData,
            'agencyData'     => $agencyData,

            'restypeMap'     => $restypeMap,
            'resfundMap'     => $resfundMap,
            'resstatusMap'   => $resstatusMap,
            'agencyMap'      => $agencyMap,

            'fundingSeries'       => $fundingSeries,
            'fundingTotalNonZero' => $fundingTotalNonZero,
        ]);
    }

    public function actionLascApi($username = null, $personal_id = null, $id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $req = Yii::$app->request;

        // 0) signature params
        $ts  = (string)$req->get('ts', '');
        $sig = (string)$req->get('sig', '');

        // 1) resolve username
        $u = null;

        if ($username !== null && trim($username) !== '') {
            $u = trim((string)$username);
        } elseif ($personal_id !== null && trim($personal_id) !== '') {
            $u = trim((string)$personal_id);
        } elseif ($id !== null) {
            $acc = Account::findOne((int)$id);
            if (!$acc) {
                Yii::$app->response->statusCode = 404;
                return ['success' => false, 'message' => 'Account not found'];
            }
            $u = (string)$acc->username;
        }

        if ($u === null || $u === '') {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => 'Missing parameter: username or personal_id or id'];
        }

        // 2) verify signature
        $secret = (string)(Yii::$app->params['lascApiKey'] ?? '');
        if ($secret === '') {
            Yii::$app->response->statusCode = 500;
            return ['success' => false, 'message' => 'Server misconfigured: missing lascApiKey'];
        }

        if ($ts === '' || $sig === '') {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'message' => 'Unauthorized: missing ts or sig'];
        }

        $tsInt = (int)$ts;
        if ($tsInt <= 0) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'message' => 'Unauthorized: invalid ts'];
        }

        // expire 5 minutes
        if (abs(time() - $tsInt) > 300) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'message' => 'Unauthorized: signature expired'];
        }

        $payload  = $u . '|' . $tsInt; // MUST match client
        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $sig)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'message' => 'Unauthorized: invalid signature'];
        }

        // 3) account exists
        $account = Account::find()->where(['username' => $u])->one();
        if (!$account) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'message' => 'Account not found for username/personal_id'];
        }

        // 4) Latest
        $researchLatest = Researchpro::find()
            ->where(['username' => $u])
            ->orderBy([Researchpro::primaryKey()[0] => SORT_DESC])
            ->limit(10)->asArray()->all();

        $articleLatest = Article::find()
            ->where(['username' => $u])
            ->orderBy([Article::primaryKey()[0] => SORT_DESC])
            ->limit(10)->asArray()->all();

        $utilLatest = Utilization::find()
            ->where(['username' => $u])
            ->orderBy([Utilization::primaryKey()[0] => SORT_DESC])
            ->limit(10)->asArray()->all();

        $serviceLatest = AcademicService::find()
            ->where(['username' => $u])
            ->orderBy([AcademicService::primaryKey()[0] => SORT_DESC])
            ->limit(10)->asArray()->all();

        // 5) KPI distinct owner+contrib
        $researchPk = Researchpro::primaryKey()[0];
        $articlePk  = Article::primaryKey()[0];
        $utilPk     = Utilization::primaryKey()[0];
        $servicePk  = AcademicService::primaryKey()[0];

        $cntResearch = (int)Researchpro::find()->where(['or',
            ['in', $researchPk, Researchpro::find()->select($researchPk)->where(['username' => $u])],
            ['in', $researchPk, WorkContributor::find()->select('ref_id')->where(['username'=>$u,'ref_type'=>'researchpro'])],
        ])->distinct()->count();

        $cntArticle = (int)Article::find()->where(['or',
            ['in', $articlePk, Article::find()->select($articlePk)->where(['username' => $u])],
            ['in', $articlePk, WorkContributor::find()->select('ref_id')->where(['username'=>$u,'ref_type'=>'article'])],
        ])->distinct()->count();

        $cntUtil = (int)Utilization::find()->where(['or',
            ['in', $utilPk, Utilization::find()->select($utilPk)->where(['username' => $u])],
            ['in', $utilPk, WorkContributor::find()->select('ref_id')->where(['username'=>$u,'ref_type'=>'utilization'])],
        ])->distinct()->count();

        $cntService = (int)AcademicService::find()->where(['or',
            ['in', $servicePk, AcademicService::find()->select($servicePk)->where(['username' => $u])],
            ['in', $servicePk, WorkContributor::find()->select('ref_id')->where(['username'=>$u,'ref_type'=>'academic_service'])],
        ])->distinct()->count();

        return [
            'success' => true,
            'message' => 'LASC API profile retrieved successfully',
            'query' => ['username' => $u, 'ts' => $tsInt],
            'account' => [
                'uid'       => (int)$account->uid,
                'username'  => (string)$account->username,
                'uname'     => (string)$account->uname,
                'luname'    => (string)$account->luname,
                'org_id'    => (int)$account->org_id,
                'dept_code' => (int)$account->dept_code,
                'position'  => (int)$account->position,
                'dayup'     => (string)$account->dayup,
            ],
            'kpi' => [
                'research' => $cntResearch,
                'article'  => $cntArticle,
                'utilization' => $cntUtil,
                'academic_service' => $cntService,
            ],
            'latest' => [
                'research' => $researchLatest,
                'article'  => $articleLatest,
                'utilization' => $utilLatest,
                'academic_service' => $serviceLatest,
            ],
        ];
    }
}
