<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\web\ForbiddenHttpException;

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
                'ruleConfig' => [
                    'class' => \app\components\HanumanRule::class,
                ],
                'only' => ['index'],
                'rules' => [
                    [
                        'actions' => ['index', 'lasc-api'],
                        'allow'   => true,
                        'roles'   => ['?', '@'], // ✅ guest + login
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::warning([
                        'route'   => $action->uniqueId,
                        'action'  => $action->id,
                        'isGuest' => Yii::$app->user->isGuest,
                        'uid'     => Yii::$app->user->id,
                        'ip'      => Yii::$app->request->userIP,
                    ], 'ACCESS_DENIED_REPORT_INDEX');

                    throw new \yii\web\ForbiddenHttpException('ไม่ได้รับอนุญาตให้เข้าถึงหน้านี้ (report/index)');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                ],
            ],
        ];
    }



    public function actionIndex()
    {
        $user       = Yii::$app->user->identity;      // อาจเป็น null
        $isGuest    = Yii::$app->user->isGuest;

        $session    = Yii::$app->session;
        $sessionOrg = $session['ty'] ?? null;

        $isSelfRole = false;

        // position 1,2 เห็นเฉพาะของตัวเอง (เฉพาะตอน login)
        if (!$isGuest && $user && ((int)$user->position === 1 || (int)$user->position === 2)) {
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
                // ✅ guest ไม่กรอง org (ให้เห็นภาพรวม)
                if (!$isGuest && $user && (int)$user->position !== 4) {
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

        // ✅ guest เห็นทุกหน่วยงาน
        if (!$isGuest && $user && (int)$user->position !== 4 && !empty($sessionOrg)) {
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

            $countuser = trim((string)$user->uname . ' ' . (string)$user->luname);
        } else {
            // ✅ guest และคนอื่น ๆ เห็นรวมทั้งหมด
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
            // ✅ guest ไม่กรอง org
            if (!$isGuest && $user && (int)$user->position !== 4) {
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
                    // ✅ guest ไม่กรอง org
                    if (!$isGuest && $user && (int)$user->position !== 4) {
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

private function relToArrayFull($rel)
{
    if (!$rel) return null;

    // hasOne AR
    if (is_object($rel) && method_exists($rel, 'getAttributes')) {
        return $rel->getAttributes();
    }

    // hasMany array of ARs
    if (is_array($rel)) {
        $out = [];
        foreach ($rel as $item) {
            if (is_object($item) && method_exists($item, 'getAttributes')) {
                $out[] = $item->getAttributes();
            } else {
                $out[] = $item;
            }
        }
        return $out;
    }

    return null;
}

private function serializeModelWithRelations($m, array $relations, string $queryUsername): array
{
    $data = $m->getAttributes();

    $ownerUsername = isset($data['username']) ? (string)$data['username'] : '';
    $isOwner = ($ownerUsername !== '' && $ownerUsername === $queryUsername);

    // ✅ ไม่เปิดเผยเจ้าของงานถ้าไม่ใช่ตัวเอง
    if (!$isOwner && array_key_exists('username', $data)) {
        $data['username'] = null; // หรือจะ unset($data['username']) ก็ได้
    }

    foreach ($relations as $relName) {
        // ✅ ถ้าไม่ใช่ owner ห้ามคืนข้อมูลบุคคลของเจ้าของงาน
        if (!$isOwner && $relName === 'user') {
            continue;
        }
        $data[$relName] = $this->relToArrayFull($m->$relName);
    }

    return $data;
}

public function actionLascApi($username = null, $personal_id = null, $id = null)
{
    Yii::$app->response->format = Response::FORMAT_JSON;
    $req = Yii::$app->request;

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
    $ts  = (string)$req->get('ts', '');
    $sig = (string)$req->get('sig', '');
    $secret = (string)(Yii::$app->params['lascApiKey'] ?? '');

    if ($secret === '' || $ts === '' || $sig === '') {
        Yii::$app->response->statusCode = 401;
        return ['success' => false, 'message' => 'Unauthorized: missing ts or sig'];
    }

    $tsInt = (int)$ts;
    if ($tsInt <= 0 || abs(time() - $tsInt) > 300) {
        Yii::$app->response->statusCode = 401;
        return ['success' => false, 'message' => 'Unauthorized: signature expired/invalid ts'];
    }

    $expected = hash_hmac('sha256', $u . '|' . $tsInt, $secret);
    if (!hash_equals($expected, $sig)) {
        Yii::$app->response->statusCode = 401;
        return ['success' => false, 'message' => 'Unauthorized: invalid signature'];
    }

    // =========================================================
    // Account + org(full) (เฉพาะคนนี้)
    // =========================================================
    $accountAR = Account::find()->with(['hasorg'])->where(['username' => $u])->one();
    if (!$accountAR) {
        Yii::$app->response->statusCode = 404;
        return ['success' => false, 'message' => 'Account not found for username'];
    }

    $accountOut = [
        'username' => (string)$accountAR->username,
        'uname'    => (string)$accountAR->uname,
        'luname'   => (string)$accountAR->luname,
        'org_id'   => (int)$accountAR->org_id,
        'org'      => $this->relToArrayFull($accountAR->hasorg),
    ];

    // =========================================================
    // helper: owner + contributor ids แล้วคืน model + relations
    // =========================================================
    $getFullItems = function(
        string $modelClass,
        string $refType,
        string $pkField,
        array  $withRelations,
        string $orderField
    ) use ($u) {

        // owner ids
        $ownerIds = $modelClass::find()
            ->select($pkField)
            ->where(['username' => $u])
            ->column();

        // contributor rows (เฉพาะของ username นี้)
        $wcRows = WorkContributor::find()
            ->select(['ref_id', 'role_code', 'contribution_pct', 'work_hours'])
            ->where(['ref_type' => $refType, 'username' => $u])
            ->asArray()
            ->all();

        $contribIds = array_map('intval', array_column($wcRows, 'ref_id'));
        $allIds = array_values(array_unique(array_merge(array_map('intval', $ownerIds), $contribIds)));

        if (empty($allIds)) return [];

        // map contributor row by ref_id
        $wcMap = [];
        foreach ($wcRows as $r) {
            $wcMap[(int)$r['ref_id']] = $r;
        }

        // fetch full ARs
        $models = $modelClass::find()
            ->with($withRelations)
            ->where(['in', $pkField, $allIds])
            ->orderBy([$orderField => SORT_DESC])
            ->all();

        $out = [];
        foreach ($models as $m) {
            $attrs = $this->serializeModelWithRelations($m, $withRelations, $u);

            $id = (int)$m->$pkField;
            $isOwner = ((string)($m->username ?? '') === $u);

            $myRole = $isOwner ? 'owner' : ($wcMap[$id]['role_code'] ?? null);
            $myPct  = $isOwner ? 100.0   : (isset($wcMap[$id]['contribution_pct']) ? (float)$wcMap[$id]['contribution_pct'] : null);
            $myHrs  = $isOwner ? null    : (isset($wcMap[$id]['work_hours']) ? (float)$wcMap[$id]['work_hours'] : null);

            $attrs['my_is_owner'] = $isOwner;
            $attrs['my_role'] = $myRole;
            $attrs['my_contribution_pct'] = $myPct;
            $attrs['my_work_hours'] = $myHrs;

            $out[] = $attrs;
        }

        return $out;
    };

    // =========================================================
    // ระบุ relations "แบบเต็ม" ตาม model ที่คุณมีจริง
    // (อิงจากไฟล์โมเดลที่คุณอัปโหลด)
    // =========================================================
    $research = $getFullItems(
        Researchpro::class,
        'researchpro',
        'projectID',
        ['hasorg','dist','amph','prov','restypes','resstatuss','habranchs','resFunds','agencys','user'],
        'projectID'
    );

    $article = $getFullItems(
        Article::class,
        'article',
        'article_id',
        ['hasorg','publi','habranch','haec','user'],
        'article_id'
    );

    $utilization = $getFullItems(
        Utilization::class,
        'utilization',
        'utilization_id',
        ['hasorg','utilization','dist','amph','prov','user'],
        'utilization_id'
    );

    $academicService = $getFullItems(
        AcademicService::class,
        'academic_service',
        'service_id',
        ['serviceType','user'],
        'service_id'
    );

    return [
        'success' => true,
        'message' => 'LASC API profile retrieved successfully',
        'meta' => [
            'version' => '2.0-owner+contributor-full-relations-private',
            'generated_at' => date('c'),
        ],
        'query' => [
            'username' => $u,
            'ts' => $tsInt,
        ],
        'account' => $accountOut,
        'data' => [
            'research' => $research,
            'article'  => $article,
            'utilization' => $utilization,
            'academic_service' => $academicService,
        ],
    ];
}



}
