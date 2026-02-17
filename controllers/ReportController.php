<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\web\ForbiddenHttpException;

// Models (Index)
use app\models\Researchpro;
use app\models\Account;
use app\models\Organize;
use app\models\Restype;
use app\models\Resstatus;
use app\models\ResFund;
use app\models\ResGency;
use app\models\Article;
use app\models\AcademicService;

// Models (API)
use app\models\Utilization;
use app\models\WorkContributor;

class ReportController extends Controller
{
     public function beforeAction($action)
    {
        // API ใช้ GET + HMAC → ไม่ต้อง CSRF
        if ($action->id === 'lasc-api') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,

                // ✅ สำคัญ: ให้ AccessControl ใช้ HanumanRule
                'ruleConfig' => [
                    'class' => \app\components\HanumanRule::class,
                ],

                // ✅ จำกัดให้ filter เฉพาะ 2 action นี้พอ (กันไปบล็อกตัวอื่น)
                'only' => ['index', 'lasc-api'],

                'rules' => [
                    [
                        'actions' => ['index','lasc-api'],
                        'allow'   => true,
                        'roles'   => ['?', '@'], // guest + login
                    ],
                ],

                // ✅ ช่วย debug ถ้ายังโดนบล็อก
                'denyCallback' => function ($rule, $action) {
                    Yii::warning([
                        'route' => $action->uniqueId,
                        'action' => $action->id,
                        'isGuest' => Yii::$app->user->isGuest,
                        'uid' => Yii::$app->user->id,
                        'ip' => Yii::$app->request->userIP,
                    ], 'ACCESS_DENIED_REPORT');
                    throw new ForbiddenHttpException('ไม่ได้รับอนุญาตให้เข้าถึงหน้านี้');
                },
            ],

            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index'    => ['GET'],
                    'lasc-api' => ['GET'],
                ],
            ],
        ];
    }


    /* =========================================================
     * actionIndex (ตามโค้ดที่คุณส่งมา)
     * ========================================================= */
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

            $countuser = trim($user->uname . ' ' . $user->luname);
        } else {
            $counttype1 = Researchpro::find()->where(['researchTypeID' => 1])->count();
            $counttype2 = Researchpro::find()->where(['researchTypeID' => 2])->count();
            $counttype3 = AcademicService::find()->count();
            $counttype4 = Article::find()->count();

            $countuser = Account::find()->count();
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

    /* =========================================================
     * Helpers สำหรับ API
     * ========================================================= */

    private function toIsoDate($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $s)) {
            [$d,$m,$y] = explode('-', $s);
            return "$y-$m-$d";
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+/', $s)) return substr($s, 0, 10);

        return $s;
    }

    private function throttle(string $key, int $limit = 60, int $seconds = 60): bool
    {
        $cacheKey = 'lasc_api_' . $key;
        $n = (int)Yii::$app->cache->get($cacheKey);

        if ($n <= 0) {
            Yii::$app->cache->set($cacheKey, 1, $seconds);
            return true;
        }
        if ($n >= $limit) return false;

        Yii::$app->cache->set($cacheKey, $n + 1, $seconds);
        return true;
    }

    private function relToArray($rel): ?array
    {
        if (!$rel) return null;
        if (is_object($rel) && method_exists($rel, 'getAttributes')) {
            return $rel->getAttributes();
        }
        if (is_array($rel)) return $rel;
        return null;
    }

    /* =========================================================
     * LASC API: Full relations + คืนผู้ร่วมหลายคนแบบเต็ม
     * Route: /research/report/lasc-api?username=...&ts=...&sig=...
     * ========================================================= */
    public function actionLascApi($username = null, $personal_id = null, $id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;

        // ---------- rate limit ----------
        $ip = $req->userIP ?? 'unknown';
        if (!$this->throttle($ip, 60, 60)) {
            Yii::$app->response->statusCode = 429;
            return ['success' => false, 'message' => 'Too Many Requests'];
        }

        // ---------- signature params ----------
        $ts  = (string)$req->get('ts', '');
        $sig = (string)$req->get('sig', '');

        // ---------- resolve username ----------
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

        // ---------- verify signature ----------
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

        $payload  = $u . '|' . $tsInt;
        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $sig)) {
            Yii::$app->response->statusCode = 401;
            return ['success' => false, 'message' => 'Unauthorized: invalid signature'];
        }

        // =========================================================
        // Account + org(full)
        // =========================================================
        $accountAR = Account::find()
            ->with(['hasorg'])
            ->where(['username' => $u])
            ->one();

        if (!$accountAR) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'message' => 'Account not found for username/personal_id'];
        }

        $accountOut = [
            'username' => (string)$accountAR->username,
            'uname'    => (string)$accountAR->uname,
            'luname'   => (string)$accountAR->luname,
            'org_id'   => (int)$accountAR->org_id,
            'org'      => $this->relToArray($accountAR->hasorg),
        ];

        // =========================================================
        // ดึง latest ARs ก่อน เพื่อเอา IDs สำหรับ contributors
        // =========================================================
        $researchARs = Researchpro::find()
            ->with(['agencys', 'restypes', 'resFunds'])
            ->where(['username' => $u])
            ->orderBy(['projectID' => SORT_DESC])
            ->limit(10)
            ->all();
        $researchIds = array_map(fn($m) => (int)$m->projectID, $researchARs);

        $articleARs = Article::find()
            ->with(['publi'])
            ->where(['username' => $u])
            ->orderBy(['article_id' => SORT_DESC])
            ->limit(10)
            ->all();
        $articleIds = array_map(fn($m) => (int)$m->article_id, $articleARs);

        $utilARs = Utilization::find()
            ->with(['utilization'])
            ->where(['username' => $u])
            ->orderBy(['utilization_id' => SORT_DESC])
            ->limit(10)
            ->all();
        $utilIds = array_map(fn($m) => (int)$m->utilization_id, $utilARs);

        $serviceARs = AcademicService::find()
            ->with(['serviceType'])
            ->where(['username' => $u])
            ->orderBy(['service_id' => SORT_DESC])
            ->limit(10)
            ->all();
        $serviceIds = array_map(fn($m) => (int)$m->service_id, $serviceARs);

        // =========================================================
        // Batch contributors (ทุก ref_type ของ latest) + map account ของผู้ร่วม
        // =========================================================
        $wcAllMap = [
            'researchpro' => [],
            'article' => [],
            'utilization' => [],
            'academic_service' => [],
        ];

        $allContributorUsernames = [];

        $or = ['or'];
        if (!empty($researchIds)) $or[] = ['and', ['ref_type' => 'researchpro'], ['in', 'ref_id', $researchIds]];
        if (!empty($articleIds))  $or[] = ['and', ['ref_type' => 'article'], ['in', 'ref_id', $articleIds]];
        if (!empty($utilIds))     $or[] = ['and', ['ref_type' => 'utilization'], ['in', 'ref_id', $utilIds]];
        if (!empty($serviceIds))  $or[] = ['and', ['ref_type' => 'academic_service'], ['in', 'ref_id', $serviceIds]];

        $wcRows = [];
        if (count($or) > 1) {
            $wcRows = WorkContributor::find()
                ->select(['ref_type', 'ref_id', 'username', 'role_code', 'contribution_pct', 'work_hours', 'sort_order'])
                ->andWhere($or)
                ->orderBy(['ref_type' => SORT_ASC, 'ref_id' => SORT_ASC, 'sort_order' => SORT_ASC])
                ->asArray()
                ->all();
        }

        foreach ($wcRows as $row) {
            $t   = (string)($row['ref_type'] ?? '');
            $rid = (int)($row['ref_id'] ?? 0);
            if ($rid <= 0 || !isset($wcAllMap[$t])) continue;

            $wcAllMap[$t][$rid][] = $row;

            $un = trim((string)($row['username'] ?? ''));
            if ($un !== '') $allContributorUsernames[$un] = true;
        }

        // map account ของผู้ร่วม (full + org full)
        $accMap = [];
        if (!empty($allContributorUsernames)) {
            $usernames = array_keys($allContributorUsernames);
            $accRows = Account::find()
                ->with(['hasorg'])
                ->where(['in', 'username', $usernames])
                ->all();

            foreach ($accRows as $a) {
                $accMap[(string)$a->username] = [
                    'username' => (string)$a->username,
                    'uname'    => (string)$a->uname,
                    'luname'   => (string)$a->luname,
                    'org_id'   => (int)$a->org_id,
                    'org'      => $this->relToArray($a->hasorg),
                ];
            }
        }

        $mapContributorRow = function(array $row) use ($accMap) {
            $un = trim((string)($row['username'] ?? ''));
            return [
                'username'         => $un !== '' ? $un : null,
                'account'          => $un !== '' ? ($accMap[$un] ?? null) : null,
                'role_code'        => $row['role_code'] ?? null,
                'contribution_pct' => isset($row['contribution_pct']) ? (float)$row['contribution_pct'] : null,
                'work_hours'       => isset($row['work_hours']) ? (float)$row['work_hours'] : null,
                'sort_order'       => isset($row['sort_order']) ? (int)$row['sort_order'] : null,
            ];
        };

        // helper หา role/pct ของผู้ query (สะดวกต่อ client)
        $findSelf = function(array $contributors) use ($u) {
            foreach ($contributors as $c) {
                if (($c['username'] ?? '') === $u) {
                    return [
                        'role_code_form' => $c['role_code'] ?? null,
                        'pct_form'       => $c['contribution_pct'] ?? null,
                    ];
                }
            }
            return ['role_code_form' => null, 'pct_form' => null];
        };

        // เติม owner เป็น contributor ถ้าไม่มีอยู่แล้ว (ไม่เขียนลง DB)
        $ensureOwnerContributor = function(array $contributors, string $ownerUsername, array $ownerAccountOut) {
            $ownerUsername = trim($ownerUsername);

            if ($ownerUsername === '') return $contributors;

            // ถ้ามี owner อยู่แล้ว ไม่ต้องเติม
            foreach ($contributors as $c) {
                if (($c['username'] ?? '') === $ownerUsername) {
                    return $contributors;
                }
            }

            // เติม owner เป็นคนแรก
            array_unshift($contributors, [
                'username'         => $ownerUsername,
                'account'          => $ownerAccountOut,   // full account + org
                'role_code'        => 'owner',
                'contribution_pct' => 100.0,
                'work_hours'       => null,
                'sort_order'       => 0,
            ]);

            return $contributors;
        };

        // =========================================================
        // Compose outputs + full relations + contributors
        // =========================================================
        $researchOut = [];
        foreach ($researchARs as $m) {
            $rid = (int)$m->projectID;

            $contributors = [];
            foreach (($wcAllMap['researchpro'][$rid] ?? []) as $row) {
                $contributors[] = $mapContributorRow($row);
            }
            $contributors = $ensureOwnerContributor($contributors, (string)$m->username, $accountOut);
            $self = $findSelf($contributors);


            $researchOut[] = [
                'projectNameTH'     => (string)$m->projectNameTH,
                'username'          => (string)$m->username,
                'projectYearsubmit' => (int)$m->projectYearsubmit,
                'budgets'           => (int)$m->budgets,
                'fundingAgencyID'   => (int)$m->fundingAgencyID,
                'researchTypeID'    => (int)$m->researchTypeID,
                'projectStartDate'  => $this->toIsoDate($m->projectStartDate),
                'projectEndDate'    => $this->toIsoDate($m->projectEndDate),
                'researchFundID'    => (int)$m->researchFundID,

                'fundingAgency' => $this->relToArray($m->agencys),
                'researchType'  => $this->relToArray($m->restypes),
                'researchFund'  => $this->relToArray($m->resFunds),

                // self role/pct
                'role_code_form' => $self['role_code_form'],
                'pct_form'       => $self['pct_form'],

                // contributors full
                'contributors'   => $contributors,
            ];
        }



        $articleOut = [];
        foreach ($articleARs as $m) {
            $aid = (int)$m->article_id;

            $contributors = [];
            foreach (($wcAllMap['article'][$aid] ?? []) as $row) {
                $contributors[] = $mapContributorRow($row);
            }
            $contributors = $ensureOwnerContributor($contributors, (string)$m->username, $accountOut);
            $self = $findSelf($contributors);

            $articleOut[] = [
                'article_th'       => (string)$m->article_th,
                'username'         => (string)$m->username,
                'publication_type' => (int)$m->publication_type,
                'article_publish'  => $this->toIsoDate($m->article_publish),
                'journal'          => (string)$m->journal,
                'refer'            => (string)$m->refer,

                'publication'      => $this->relToArray($m->publi),

                // self role/pct
                'role_code_form'   => $self['role_code_form'],
                'pct_form'         => $self['pct_form'],

                // contributors full
                'contributors'     => $contributors,
            ];
        }

        $utilOut = [];
        foreach ($utilARs as $m) {
            $uid = (int)$m->utilization_id;

            $contributors = [];
            foreach (($wcAllMap['utilization'][$uid] ?? []) as $row) {
                $contributors[] = $mapContributorRow($row);
            }
            $contributors = $ensureOwnerContributor($contributors, (string)$m->username, $accountOut);
            $utilOut[] = [
                'project_name'       => (string)$m->project_name,
                'username'           => (string)$m->username,
                'utilization_type'   => (int)$m->utilization_type,
                'utilization_date'   => $this->toIsoDate($m->utilization_date),
                'utilization_detail' => (string)$m->utilization_detail,
                'utilization_refer'  => (string)$m->utilization_refer,

                'utilizationType'    => $this->relToArray($m->utilization),
                'contributors'       => $contributors,
            ];
        }

        $serviceOut = [];
        foreach ($serviceARs as $m) {
            $sid = (int)$m->service_id;

            $contributors = [];
            foreach (($wcAllMap['academic_service'][$sid] ?? []) as $row) {
                $contributors[] = $mapContributorRow($row);
            }
            $contributors = $ensureOwnerContributor($contributors, (string)$m->username, $accountOut);
            $serviceOut[] = [
                'username'        => (string)$m->username,
                'service_date'    => $this->toIsoDate($m->service_date),
                'type_id'         => (int)$m->type_id,
                'title'           => (string)$m->title,
                'location'        => (string)$m->location,
                'work_desc'       => (string)$m->work_desc,
                'hours'           => (float)$m->hours,
                'reference_url'   => (string)$m->reference_url,
                'attachment_path' => $m->attachment_path,

                'serviceType'     => $this->relToArray($m->serviceType),
                'contributors'    => $contributors,
            ];
        }

        return [
            'success' => true,
            'message' => 'LASC API profile retrieved successfully',
            'meta' => [
                'version' => '1.2-full-relations+contributors',
                'generated_at' => date('c'),
            ],
            'query' => [
                'username' => $u,
                'ts' => $tsInt,
            ],
            'account' => $accountOut,
            'latest' => [
                'research' => $researchOut,
                'article'  => $articleOut,
                'utilization' => $utilOut,
                'academic_service' => $serviceOut,
            ],
        ];
    }
}
