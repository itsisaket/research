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
private function toDMY($v): ?string
{
    if ($v === null) return null;
    $s = trim((string)$v);
    if ($s === '') return null;

    // already d-m-Y
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $s)) return $s;

    // yyyy-mm-dd or yyyy-mm-dd HH:ii:ss
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
        $y = substr($s, 0, 4);
        $m = substr($s, 5, 2);
        $d = substr($s, 8, 2);
        return $d . '-' . $m . '-' . $y;
    }

    // dd/mm/yyyy
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $s)) {
        [$d,$m,$y] = explode('/', $s);
        return $d . '-' . $m . '-' . $y;
    }

    return $s;
}

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

private function buildMyContribMap(string $refType, string $username): array
{
    $rows = WorkContributor::find()
        ->select(['ref_id','role_code','contribution_pct','work_hours'])
        ->where(['ref_type' => $refType, 'username' => $username])
        ->asArray()
        ->all();

    $map = [];
    foreach ($rows as $r) {
        $rid = (int)$r['ref_id'];
        $map[$rid] = [
            'my_is_owner' => false,
            'my_role' => $r['role_code'] ?? null,
            'my_contribution_pct' => isset($r['contribution_pct']) ? (float)$r['contribution_pct'] : null,
            'my_work_hours' => isset($r['work_hours']) ? (float)$r['work_hours'] : null,
        ];
    }
    return $map;
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
    if ($secret === '') {
        Yii::$app->response->statusCode = 500;
        return ['success' => false, 'message' => 'Server misconfigured: missing lascApiKey'];
    }
    if ($ts === '' || $sig === '') {
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
    // Account (flatten org)
    // =========================================================
    $accountAR = Account::find()->with(['hasorg'])->where(['username' => $u])->one();
    if (!$accountAR) {
        Yii::$app->response->statusCode = 404;
        return ['success' => false, 'message' => 'Account not found for username'];
    }

    $org = $accountAR->hasorg;
    $accountOut = [
        'username' => (string)$accountAR->username,
        'uname'    => (string)$accountAR->uname,
        'luname'   => (string)$accountAR->luname,
        'org_id'   => (int)$accountAR->org_id,
        'org_name' => $org ? (string)$org->org_name : null,
    ];

    // =========================================================
    // MY CONTRIBUTION MAP (ไม่เปิดเผยคนอื่น)
    // =========================================================
    $myResearch = $this->buildMyContribMap('researchpro', $u);
    $myArticle  = $this->buildMyContribMap('article', $u);
    $myUtil     = $this->buildMyContribMap('utilization', $u);
    $myService  = $this->buildMyContribMap('academic_service', $u);

    // =========================================================
    // Helper: รวม owner + contributor ids
    // =========================================================
    $mergeIds = function(array $ownerIds, array $contribMap): array {
        $ids = array_merge(array_map('intval', $ownerIds), array_map('intval', array_keys($contribMap)));
        $ids = array_values(array_unique($ids));
        return $ids;
    };

    // =========================================================
    // RESEARCH (flatten relations)
    // =========================================================
    $researchOwnerIds = Researchpro::find()->select('projectID')->where(['username' => $u])->column();
    $researchIds = $mergeIds($researchOwnerIds, $myResearch);

    $researchOut = [];
    if (!empty($researchIds)) {
        $researchARs = Researchpro::find()
            ->with(['hasorg','dist','amph','prov','restypes','resstatuss','habranchs','resFunds','agencys'])
            ->where(['in', 'projectID', $researchIds])
            ->orderBy(['projectID' => SORT_DESC])
            ->all();

        foreach ($researchARs as $m) {
            $rid = (int)$m->projectID;
            $isOwner = ((string)$m->username === $u);

            $my = $isOwner
                ? ['my_is_owner'=>true,'my_role'=>'owner','my_contribution_pct'=>100.0,'my_work_hours'=>null]
                : ($myResearch[$rid] ?? ['my_is_owner'=>false,'my_role'=>null,'my_contribution_pct'=>null,'my_work_hours'=>null]);

            $researchOut[] = array_merge([
                'projectNameTH'     => (string)$m->projectNameTH,
                'projectNameEN'     => (string)$m->projectNameEN,
                'projectYearsubmit' => (int)$m->projectYearsubmit,
                'budgets'           => (int)$m->budgets,
                'projectStartDate'  => $this->toDMY($m->projectStartDate),
                'projectEndDate'    => $this->toDMY($m->projectEndDate),
                'researchArea'      => (string)$m->researchArea,

                'org_id'   => (int)$m->org_id,
                'org_name' => $m->hasorg ? (string)$m->hasorg->org_name : null,

                'DISTRICT_CODE' => $m->dist ? (string)$m->dist->DISTRICT_CODE : null,
                'DISTRICT_NAME' => $m->dist ? (string)$m->dist->DISTRICT_NAME : null,

                'AMPHUR_CODE' => $m->amph ? (string)$m->amph->AMPHUR_CODE : null,
                'AMPHUR_NAME' => $m->amph ? (string)$m->amph->AMPHUR_NAME : null,

                'PROVINCE_CODE' => $m->prov ? (string)$m->prov->PROVINCE_CODE : null,
                'PROVINCE_NAME' => $m->prov ? (string)$m->prov->PROVINCE_NAME : null,

                'restypeid'   => $m->restypes ? (int)$m->restypes->restypeid : null,
                'restypename' => $m->restypes ? (string)$m->restypes->restypename : null,

                'statusid'   => $m->resstatuss ? (int)$m->resstatuss->statusid : null,
                'statusname' => $m->resstatuss ? (string)$m->resstatuss->statusname : null,

                'branch_id'   => $m->habranchs ? (int)$m->habranchs->branch_id : null,
                'branch_name' => $m->habranchs ? (string)$m->habranchs->branch_name : null,

                'researchFundID'   => (int)$m->researchFundID,
                'researchFundName' => $m->resFunds ? (string)$m->resFunds->researchFundName : null,

                'fundingAgencyID'   => (int)$m->fundingAgencyID,
                'fundingAgencyName' => $m->agencys ? (string)$m->agencys->fundingAgencyName : null,
                'fundingAgency'     => $m->agencys ? (string)$m->agencys->fundingAgency : null,
            ], $my);
        }
    }

    // =========================================================
    // ARTICLE (flatten relations)
    // =========================================================
    $articleOwnerIds = Article::find()->select('article_id')->where(['username' => $u])->column();
    $articleIds = $mergeIds($articleOwnerIds, $myArticle);

    $articleOut = [];
    if (!empty($articleIds)) {
        $articleARs = Article::find()
            ->with(['hasorg','publi','habranch','haec'])
            ->where(['in', 'article_id', $articleIds])
            ->orderBy(['article_id' => SORT_DESC])
            ->all();

        foreach ($articleARs as $m) {
            $aid = (int)$m->article_id;
            $isOwner = ((string)$m->username === $u);

            $my = $isOwner
                ? ['my_is_owner'=>true,'my_role'=>'owner','my_contribution_pct'=>100.0,'my_work_hours'=>null]
                : ($myArticle[$aid] ?? ['my_is_owner'=>false,'my_role'=>null,'my_contribution_pct'=>null,'my_work_hours'=>null]);

            $articleOut[] = array_merge([
                'article_th'      => (string)$m->article_th,
                'article_eng'     => (string)$m->article_eng,
                'article_publish' => $this->toDMY($m->article_publish),
                'journal'         => (string)$m->journal,
                'refer'           => (string)$m->refer,
                'research_id'     => $m->research_id !== null ? (int)$m->research_id : null,
                'documentid'      => $m->documentid ?? null,

                'org_id'   => (int)$m->org_id,
                'org_name' => $m->hasorg ? (string)$m->hasorg->org_name : null,

                'publication_type'   => (int)$m->publication_type,
                'publication_name'   => $m->publi ? (string)$m->publi->publication_name : null,
                'publication_detail' => $m->publi ? (string)($m->publi->publication_detail ?? '') : null,

                'branch_id'   => $m->habranch ? (int)$m->habranch->branch_id : null,
                'branch_name' => $m->habranch ? (string)$m->habranch->branch_name : null,

                'status_ec' => (int)$m->status_ec,
                'ec_name'   => $m->haec ? (string)($m->haec->ec_name ?? null) : null,
            ], $my);
        }
    }

    // =========================================================
    // UTILIZATION (flatten relations)
    // =========================================================
    $utilOwnerIds = Utilization::find()->select('utilization_id')->where(['username' => $u])->column();
    $utilIds = $mergeIds($utilOwnerIds, $myUtil);

    $utilOut = [];
    if (!empty($utilIds)) {
        $utilARs = Utilization::find()
            ->with(['hasorg','utilization','dist','amph','prov'])
            ->where(['in', 'utilization_id', $utilIds])
            ->orderBy(['utilization_id' => SORT_DESC])
            ->all();

        foreach ($utilARs as $m) {
            $uid = (int)$m->utilization_id;
            $isOwner = ((string)$m->username === $u);

            $my = $isOwner
                ? ['my_is_owner'=>true,'my_role'=>'owner','my_contribution_pct'=>100.0,'my_work_hours'=>null]
                : ($myUtil[$uid] ?? ['my_is_owner'=>false,'my_role'=>null,'my_contribution_pct'=>null,'my_work_hours'=>null]);

            $utilOut[] = array_merge([
                'project_name'       => (string)$m->project_name,
                'utilization_add'    => (string)$m->utilization_add,
                'utilization_date'   => $this->toDMY($m->utilization_date),
                'utilization_detail' => (string)$m->utilization_detail,
                'utilization_refer'  => (string)$m->utilization_refer,
                'research_id'        => $m->research_id !== null ? (int)$m->research_id : null,
                'documentid'         => $m->documentid ?? null,

                'org_id'   => (int)$m->org_id,
                'org_name' => $m->hasorg ? (string)$m->hasorg->org_name : null,

                'utilization_type'      => (int)$m->utilization_type,
                'utilization_type_name' => $m->utilization ? (string)$m->utilization->utilization_type_name : null,

                'DISTRICT_CODE' => $m->dist ? (string)$m->dist->DISTRICT_CODE : null,
                'DISTRICT_NAME' => $m->dist ? (string)$m->dist->DISTRICT_NAME : null,

                'AMPHUR_CODE' => $m->amph ? (string)$m->amph->AMPHUR_CODE : null,
                'AMPHUR_NAME' => $m->amph ? (string)$m->amph->AMPHUR_NAME : null,

                'PROVINCE_CODE' => $m->prov ? (string)$m->prov->PROVINCE_CODE : null,
                'PROVINCE_NAME' => $m->prov ? (string)$m->prov->PROVINCE_NAME : null,
                'GEO_ID'        => $m->prov ? (int)$m->prov->GEO_ID : null,
            ], $my);
        }
    }

    // =========================================================
    // ACADEMIC SERVICE (flatten relations)
    // =========================================================
    $serviceOwnerIds = AcademicService::find()->select('service_id')->where(['username' => $u])->column();
    $serviceIds = $mergeIds($serviceOwnerIds, $myService);

    $serviceOut = [];
    if (!empty($serviceIds)) {
        $serviceARs = AcademicService::find()
            ->with(['serviceType'])
            ->where(['in', 'service_id', $serviceIds])
            ->orderBy(['service_id' => SORT_DESC])
            ->all();

        foreach ($serviceARs as $m) {
            $sid = (int)$m->service_id;
            $isOwner = ((string)$m->username === $u);

            $my = $isOwner
                ? ['my_is_owner'=>true,'my_role'=>'owner','my_contribution_pct'=>100.0,'my_work_hours'=>null]
                : ($myService[$sid] ?? ['my_is_owner'=>false,'my_role'=>null,'my_contribution_pct'=>null,'my_work_hours'=>null]);

            $serviceOut[] = array_merge([
                'service_date'    => $this->toDMY($m->service_date),
                'title'           => (string)$m->title,
                'location'        => (string)$m->location,
                'work_desc'       => (string)$m->work_desc,
                'hours'           => (float)$m->hours,
                'reference_url'   => (string)$m->reference_url,
                'attachment_path' => $m->attachment_path,
                'status'          => (int)$m->status,
                'note'            => (string)$m->note,
                'updated_at'      => $m->updated_at,

                'type_id'   => (int)$m->type_id,
                'type_name' => $m->serviceType ? (string)$m->serviceType->type_name : null,
            ], $my);
        }
    }

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
            'research' => $researchOut,
            'article'  => $articleOut,
            'utilization' => $utilOut,
            'academic_service' => $serviceOut,
        ],
    ];
}




}
