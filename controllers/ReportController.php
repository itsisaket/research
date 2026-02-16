<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\helpers\ArrayHelper;

use app\models\Account;
use app\models\Researchpro;
use app\models\Article;
use app\models\Utilization;
use app\models\AcademicService;
use app\models\WorkContributor;

// maps
use app\models\Organize;
use app\models\Restype;
use app\models\ResFund;
use app\models\ResGency;

// NOTE: ถ้าชื่อคลาสคุณต่างจากนี้ ให้แก้ให้ตรงโปรเจกต์
use app\models\Publication;
use app\models\Utilization_type;
use app\models\AcademicServiceType;

class ReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // เปิดให้เรียกได้ แต่คุมด้วย HMAC ใน actionLascApi()
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
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    /** แปลงวันให้เป็น YYYY-MM-DD (รองรับ dd-mm-yyyy และ datetime) */
    private function toIsoDate($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;                 // yyyy-mm-dd
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $s)) {                          // dd-mm-yyyy
            [$d, $m, $y] = explode('-', $s);
            return $y . '-' . $m . '-' . $d;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+/', $s)) return substr($s, 0, 10); // datetime -> date

        return $s; // fallback
    }

    /** rate limit กันยิงถี่ (ต่อ IP) */
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

    /**
     * LASC API (JSON)
     * - HMAC: sig = HMAC_SHA256(username|ts, params['lascApiKey'])
     * - Field ตามสเปก + แปลงรหัสเป็นชื่อ (label) จากโมเดลอ้างอิง
     */
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

        // ---------- account (field ตามสเปก) ----------
        $account = Account::find()
            ->select(['username', 'uname', 'luname', 'org_id'])
            ->where(['username' => $u])
            ->asArray()
            ->one();

        if (!$account) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'message' => 'Account not found for username/personal_id'];
        }

        // =========================================================
        // MAP: รหัส -> ชื่อ (โหลดครั้งเดียว)
        // =========================================================
        $orgMap = ArrayHelper::map(
            Organize::find()->select(['org_id', 'org_name'])->asArray()->all(),
            'org_id',
            'org_name'
        );

        $fundingAgencyMap = ArrayHelper::map(
            ResGency::find()->select(['fundingAgencyID', 'fundingAgencyName'])->asArray()->all(),
            'fundingAgencyID',
            'fundingAgencyName'
        );

        $researchFundMap = ArrayHelper::map(
            ResFund::find()->select(['researchFundID', 'researchFundName'])->asArray()->all(),
            'researchFundID',
            'researchFundName'
        );

        $researchTypeMap = ArrayHelper::map(
            Restype::find()->select(['restypeid', 'restypename'])->asArray()->all(),
            'restypeid',
            'restypename'
        );

        // optional maps (ถ้าไม่มีตาราง/คลาสให้แก้ชื่อหรือคอมเมนต์ออก)
        $publicationMap = [];
        try {
            $publicationMap = ArrayHelper::map(
                Publication::find()->select(['publication_type', 'publication_name'])->asArray()->all(),
                'publication_type',
                'publication_name'
            );
        } catch (\Throwable $e) {
            $publicationMap = [];
        }

        $utilTypeMap = [];
        try {
            $utilTypeMap = ArrayHelper::map(
                Utilization_type::find()->select(['utilization_type', 'utilization_type_name'])->asArray()->all(),
                'utilization_type',
                'utilization_type_name'
            );
        } catch (\Throwable $e) {
            $utilTypeMap = [];
        }

        $serviceTypeMap = [];
        try {
            $serviceTypeMap = ArrayHelper::map(
                AcademicServiceType::find()->select(['type_id', 'type_name'])->asArray()->all(),
                'type_id',
                'type_name'
            );
        } catch (\Throwable $e) {
            $serviceTypeMap = [];
        }

        // =========================================================
        // latest.research (field ตามสเปก + label + role/pct)
        // =========================================================
        $researchRaw = Researchpro::find()
            ->select([
                'projectID',
                'projectNameTH',
                'username',
                'projectYearsubmit',
                'budgets',
                'fundingAgencyID',
                'researchTypeID',
                'projectStartDate',
                'projectEndDate',
                'researchFundID',
            ])
            ->where(['username' => $u])
            ->orderBy(['projectID' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();

        $researchIds = array_values(array_filter(array_map(function ($r) {
            return isset($r['projectID']) ? (int)$r['projectID'] : 0;
        }, $researchRaw)));

        $wcResearchMap = []; // [projectID] => role/pct ของ "คนนี้"
        if (!empty($researchIds)) {
            $rows = WorkContributor::find()
                ->select(['ref_id', 'role_code', 'contribution_pct'])
                ->where(['ref_type' => 'researchpro', 'username' => $u])
                ->andWhere(['in', 'ref_id', $researchIds])
                ->orderBy(['sort_order' => SORT_ASC, 'ref_id' => SORT_ASC])
                ->asArray()
                ->all();

            foreach ($rows as $wc) {
                $rid = (int)($wc['ref_id'] ?? 0);
                if ($rid && !isset($wcResearchMap[$rid])) {
                    $wcResearchMap[$rid] = [
                        'role_code_form' => $wc['role_code'] ?? null,
                        'pct_form'       => isset($wc['contribution_pct']) ? (float)$wc['contribution_pct'] : null,
                    ];
                }
            }
        }

        $researchLatest = [];
        foreach ($researchRaw as $r) {
            $rid = (int)($r['projectID'] ?? 0);
            $fundingId = (int)($r['fundingAgencyID'] ?? 0);
            $typeId    = (int)($r['researchTypeID'] ?? 0);
            $fundId    = (int)($r['researchFundID'] ?? 0);

            $researchLatest[] = [
                'projectNameTH'     => $r['projectNameTH'] ?? null,
                'username'          => $r['username'] ?? null,
                'projectYearsubmit' => $r['projectYearsubmit'] ?? null,
                'budgets'           => $r['budgets'] ?? null,

                'fundingAgencyID'   => $fundingId ?: null,
                'fundingAgencyName' => $fundingAgencyMap[$fundingId] ?? null,

                'researchTypeID'    => $typeId ?: null,
                'researchTypeName'  => $researchTypeMap[$typeId] ?? null,

                'projectStartDate'  => $this->toIsoDate($r['projectStartDate'] ?? null),
                'projectEndDate'    => $this->toIsoDate($r['projectEndDate'] ?? null),

                'researchFundID'    => $fundId ?: null,
                'researchFundName'  => $researchFundMap[$fundId] ?? null,

                'role_code_form'    => $wcResearchMap[$rid]['role_code_form'] ?? null,
                'pct_form'          => $wcResearchMap[$rid]['pct_form'] ?? null,
            ];
        }

        // =========================================================
        // latest.article (field ตามสเปก + label + role/pct)
        // =========================================================
        $articleRaw = Article::find()
            ->select([
                'article_id',
                'article_th',
                'username',
                'publication_type',
                'article_publish',
                'journal',
                'refer',
            ])
            ->where(['username' => $u])
            ->orderBy(['article_id' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();

        $articleIds = array_values(array_filter(array_map(function ($a) {
            return isset($a['article_id']) ? (int)$a['article_id'] : 0;
        }, $articleRaw)));

        $wcArticleMap = []; // [article_id] => role/pct ของ "คนนี้"
        if (!empty($articleIds)) {
            $rows = WorkContributor::find()
                ->select(['ref_id', 'role_code', 'contribution_pct'])
                ->where(['ref_type' => 'article', 'username' => $u])
                ->andWhere(['in', 'ref_id', $articleIds])
                ->orderBy(['sort_order' => SORT_ASC, 'ref_id' => SORT_ASC])
                ->asArray()
                ->all();

            foreach ($rows as $wc) {
                $aid = (int)($wc['ref_id'] ?? 0);
                if ($aid && !isset($wcArticleMap[$aid])) {
                    $wcArticleMap[$aid] = [
                        'role_code_form' => $wc['role_code'] ?? null,
                        'pct_form'       => isset($wc['contribution_pct']) ? (float)$wc['contribution_pct'] : null,
                    ];
                }
            }
        }

        $articleLatest = [];
        foreach ($articleRaw as $a) {
            $aid = (int)($a['article_id'] ?? 0);
            $pubId = (int)($a['publication_type'] ?? 0);

            $articleLatest[] = [
                'article_th'       => $a['article_th'] ?? null,
                'username'         => $a['username'] ?? null,

                'publication_type' => $pubId ?: null,
                'publication_name' => $publicationMap[$pubId] ?? null,

                'article_publish'  => $this->toIsoDate($a['article_publish'] ?? null),
                'journal'          => $a['journal'] ?? null,
                'refer'            => $a['refer'] ?? null,

                'role_code_form'   => $wcArticleMap[$aid]['role_code_form'] ?? null,
                'pct_form'         => $wcArticleMap[$aid]['pct_form'] ?? null,
            ];
        }

        // =========================================================
        // latest.utilization (field ตามสเปก + label)
        // =========================================================
        $utilRaw = Utilization::find()
            ->select([
                'project_name',
                'username',
                'utilization_type',
                'utilization_date',
                'utilization_detail',
                'utilization_refer',
            ])
            ->where(['username' => $u])
            ->orderBy(['utilization_id' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();

        foreach ($utilRaw as &$uu) {
            $tid = (int)($uu['utilization_type'] ?? 0);
            $uu['utilization_type_name'] = $utilTypeMap[$tid] ?? null;
            $uu['utilization_date'] = $this->toIsoDate($uu['utilization_date'] ?? null);
        }
        unset($uu);

        // =========================================================
        // latest.academic_service (field ตามสเปก + label)
        // =========================================================
        $serviceRaw = AcademicService::find()
            ->select([
                'username',
                'service_date',
                'type_id',
                'title',
                'location',
                'work_desc',
                'hours',
                'reference_url',
                'attachment_path',
            ])
            ->where(['username' => $u])
            ->orderBy(['service_id' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();

        foreach ($serviceRaw as &$ss) {
            $tid = (int)($ss['type_id'] ?? 0);
            $ss['type_name'] = $serviceTypeMap[$tid] ?? null;
            $ss['service_date'] = $this->toIsoDate($ss['service_date'] ?? null);
        }
        unset($ss);

        // =========================================================
        // return
        // =========================================================
        $orgId = (int)($account['org_id'] ?? 0);

        return [
            'success' => true,
            'message' => 'LASC API profile retrieved successfully',
            'meta' => [
                'version' => '1.0',
                'generated_at' => date('c'),
            ],
            'query' => [
                'username' => $u,
                'ts' => $tsInt,
            ],
            'account' => [
                'username' => (string)$account['username'],
                'uname'    => (string)$account['uname'],
                'luname'   => (string)$account['luname'],
                // เพิ่ม label ของ org (ไม่ทับของเดิม)
                'org_id'   => $orgId ?: null,
                'org_name' => $orgMap[$orgId] ?? null,
            ],
            'latest' => [
                'research' => $researchLatest,
                'article'  => $articleLatest,
                'utilization' => $utilRaw,
                'academic_service' => $serviceRaw,
            ],
        ];
    }
}
