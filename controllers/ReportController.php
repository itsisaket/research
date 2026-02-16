<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

use app\models\Account;
use app\models\Researchpro;
use app\models\Article;
use app\models\Utilization;
use app\models\AcademicService;
use app\models\WorkContributor;

// ใช้ใน actionIndex เดิมของคุณ
use app\models\Organize;
use app\models\Restype;
use app\models\Resstatus;
use app\models\ResFund;
use app\models\ResGency;

class ReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // ✅ เปิดให้เรียกได้ทุกคน แต่คุมด้วย HMAC ใน actionLascApi()
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

    // =========================
    // (เดิม) actionIndex ของคุณ
    // =========================
    public function actionIndex()
    {
        // ✅ คุณใช้ actionIndex ยาวมากอยู่แล้ว ให้คงของเดิมได้
        // ที่นี่ผมไม่แตะ เพื่อไม่ทำให้กราฟ/สรุปเสีย
        return $this->render('index');
    }

    /* =========================================================
     * Helpers
     * ========================================================= */

    /** แปลงวันให้เป็น YYYY-MM-DD (รองรับ dd-mm-yyyy และ datetime) */
    private function toIsoDate($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;                 // yyyy-mm-dd
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $s)) {                          // dd-mm-yyyy
            [$d, $m, $y] = explode('-', $s);
            return "$y-$m-$d";
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

    /** คืน array ของ model relation แบบ “เต็ม” (ทุกคอลัมน์) หรือ null */
    private function relToArray($rel): ?array
    {
        if (!$rel) return null;
        // ActiveRecord -> attributes ทั้งหมด
        if (is_object($rel) && method_exists($rel, 'getAttributes')) {
            return $rel->getAttributes();
        }
        // asArray() แล้วอาจได้เป็น array อยู่แล้ว
        if (is_array($rel)) return $rel;
        return null;
    }

    /**
     * LASC API (JSON) - FULL RELATIONS
     *
     * - HMAC: sig = HMAC_SHA256(username|ts, params['lascApiKey'])
     * - field หลักตามสเปกของคุณ + แนบ relation objects แบบเต็ม
     *
     * Output:
     * account: username, uname, luname + org(full)
     * latest.research: fields ที่กำหนด + fundingAgency(full) + researchType(full) + researchFund(full) + role/pct
     * latest.article:  fields ที่กำหนด + publication(full) + role/pct
     * latest.utilization: fields ที่กำหนด + utilizationType(full)
     * latest.academic_service: fields ที่กำหนด + serviceType(full)
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

        // =========================================================
        // 1) Account + Org(full)
        // =========================================================
        $accountAR = Account::find()
            ->with(['hasorg']) // Organize
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
        // 2) ดึง WorkContributor ของ "คนนี้" เฉพาะ ref_type ที่ต้องใช้
        //     ทำ map สำหรับ research/article: [ref_type][ref_id] => role/pct
        // =========================================================
        $wcRows = WorkContributor::find()
            ->select(['ref_type', 'ref_id', 'role_code', 'contribution_pct', 'sort_order'])
            ->where(['username' => $u])
            ->andWhere(['in', 'ref_type', ['researchpro', 'article']])
            ->orderBy(['ref_type' => SORT_ASC, 'ref_id' => SORT_ASC, 'sort_order' => SORT_ASC])
            ->asArray()
            ->all();

        $wcMap = [
            'researchpro' => [],
            'article' => [],
        ];

        foreach ($wcRows as $wc) {
            $t = (string)($wc['ref_type'] ?? '');
            $rid = (int)($wc['ref_id'] ?? 0);
            if ($rid <= 0 || !isset($wcMap[$t])) continue;

            // เอาแถวแรกเป็นค่า (กันซ้ำ)
            if (!isset($wcMap[$t][$rid])) {
                $wcMap[$t][$rid] = [
                    'role_code_form' => $wc['role_code'] ?? null,
                    'pct_form'       => isset($wc['contribution_pct']) ? (float)$wc['contribution_pct'] : null,
                ];
            }
        }

        // =========================================================
        // 3) latest.research + relations full
        //    ใช้ relations ที่คุณมีแล้วใน Researchpro:
        //      - getAgencys()   => ResGency
        //      - getRestypes()  => Restype
        //      - getResFunds()  => ResFund
        // =========================================================
        $researchARs = Researchpro::find()
            ->with(['agencys', 'restypes', 'resFunds'])
            ->where(['username' => $u])
            ->orderBy(['projectID' => SORT_DESC])
            ->limit(10)
            ->all();

        $researchOut = [];
        foreach ($researchARs as $m) {
            $rid = (int)$m->projectID;
            $researchOut[] = [
                // fields ตามสเปก
                'projectNameTH'     => (string)$m->projectNameTH,
                'username'          => (string)$m->username,
                'projectYearsubmit' => (int)$m->projectYearsubmit,
                'budgets'           => (int)$m->budgets,
                'fundingAgencyID'   => (int)$m->fundingAgencyID,
                'researchTypeID'    => (int)$m->researchTypeID,
                'projectStartDate'  => $this->toIsoDate($m->projectStartDate),
                'projectEndDate'    => $this->toIsoDate($m->projectEndDate),
                'researchFundID'    => (int)$m->researchFundID,

                // role/pct ของ “คนที่ query”
                'role_code_form'    => $wcMap['researchpro'][$rid]['role_code_form'] ?? null,
                'pct_form'          => $wcMap['researchpro'][$rid]['pct_form'] ?? null,

                // ✅ FULL RELATIONS
                'fundingAgency' => $this->relToArray($m->agencys),
                'researchType'  => $this->relToArray($m->restypes),
                'researchFund'  => $this->relToArray($m->resFunds),
            ];
        }

        // =========================================================
        // 4) latest.article + publication(full)
        //    Article มี relation: getPubli() => Publication
        // =========================================================
        $articleARs = Article::find()
            ->with(['publi'])
            ->where(['username' => $u])
            ->orderBy(['article_id' => SORT_DESC])
            ->limit(10)
            ->all();

        $articleOut = [];
        foreach ($articleARs as $m) {
            $aid = (int)$m->article_id;
            $articleOut[] = [
                // fields ตามสเปก
                'article_th'       => (string)$m->article_th,
                'username'         => (string)$m->username,
                'publication_type' => (int)$m->publication_type,
                'article_publish'  => $this->toIsoDate($m->article_publish),
                'journal'          => (string)$m->journal,
                'refer'            => (string)$m->refer,

                // role/pct ของ “คนที่ query”
                'role_code_form'   => $wcMap['article'][$aid]['role_code_form'] ?? null,
                'pct_form'         => $wcMap['article'][$aid]['pct_form'] ?? null,

                // ✅ FULL RELATION
                'publication'      => $this->relToArray($m->publi),
            ];
        }

        // =========================================================
        // 5) latest.utilization + utilizationType(full)
        //    Utilization มี relation: getUtilization() => Utilization_type
        // =========================================================
        $utilARs = Utilization::find()
            ->with(['utilization'])
            ->where(['username' => $u])
            ->orderBy(['utilization_id' => SORT_DESC])
            ->limit(10)
            ->all();

        $utilOut = [];
        foreach ($utilARs as $m) {
            $utilOut[] = [
                // fields ตามสเปก
                'project_name'       => (string)$m->project_name,
                'username'           => (string)$m->username,
                'utilization_type'   => (int)$m->utilization_type,
                'utilization_date'   => $this->toIsoDate($m->utilization_date),
                'utilization_detail' => (string)$m->utilization_detail,
                'utilization_refer'  => (string)$m->utilization_refer,

                // ✅ FULL RELATION
                'utilizationType'    => $this->relToArray($m->utilization),
            ];
        }

        // =========================================================
        // 6) latest.academic_service + serviceType(full)
        //    AcademicService มี relation: getServiceType() => AcademicServiceType
        // =========================================================
        $serviceARs = AcademicService::find()
            ->with(['serviceType'])
            ->where(['username' => $u])
            ->orderBy(['service_id' => SORT_DESC])
            ->limit(10)
            ->all();

        $serviceOut = [];
        foreach ($serviceARs as $m) {
            $serviceOut[] = [
                // fields ตามสเปก
                'username'        => (string)$m->username,
                'service_date'    => $this->toIsoDate($m->service_date),
                'type_id'         => (int)$m->type_id,
                'title'           => (string)$m->title,
                'location'        => (string)$m->location,
                'work_desc'       => (string)$m->work_desc,
                'hours'           => (float)$m->hours,
                'reference_url'   => (string)$m->reference_url,
                'attachment_path' => $m->attachment_path,

                // ✅ FULL RELATION
                'serviceType'     => $this->relToArray($m->serviceType),
            ];
        }

        // =========================================================
        // 7) Return JSON
        // =========================================================
        return [
            'success' => true,
            'message' => 'LASC API profile retrieved successfully',
            'meta' => [
                'version' => '1.1-full-relations',
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
