<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use app\models\Researchpro;
use app\models\Account;
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
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow'   => true,
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [],
            ],
        ];
    }

    public function actionIndex()
    {
        $user        = Yii::$app->user->identity;
        $session     = Yii::$app->session;
        $sessionOrg  = $session['ty'] ?? null;
        $isSelfRole  = false;

        // สมมติ 1,2 = เห็นเฉพาะของตัวเอง
        if ($user && ($user->position == 1 || $user->position == 2)) {
            $isSelfRole = true;
        }

        /* =========================================================
         * 1. กราฟ 5 ปีย้อนหลัง (นับจาก tb_researchpro.projectYearsubmit)
         * ========================================================= */
        $seriesY     = [];
        $categoriesY = [];

        $currentYearAD = (int) date('Y');
        $currentYearTH = $currentYearAD + 543;

        // เช่น 2568 → 2568, 2567, 2566, 2565, 2564
        $yearsTH = [];
        for ($i = 0; $i < 5; $i++) {
            $yearsTH[] = $currentYearTH - $i;
        }
        // ให้เรียงจากน้อย → มาก บนกราฟ
        $yearsTH = array_reverse($yearsTH);

        foreach ($yearsTH as $yearTH) {
            $query = Researchpro::find()
                ->where(['projectYearsubmit' => $yearTH]);

            // กรองตามสิทธิ์
            if ($isSelfRole) {
                $query->andWhere(['uid' => $user->uid]);
            } else {
                if ($user && $user->position != 4) {
                    if (!empty($sessionOrg)) {
                        $query->andWhere(['org_id' => $sessionOrg]);
                    } elseif (!empty($user->org_id)) {
                        $query->andWhere(['org_id' => $user->org_id]);
                    }
                }
            }

            $count = (int) $query->count();
            $seriesY[]     = $count;
            $categoriesY[] = (string) $yearTH;
        }

        /* =========================================================
         * 2. กราฟแยกตามหน่วยงาน (จาก Organize → นับใน Researchpro)
         * ========================================================= */
        $seriesO     = [];
        $categoriesO = [];

        $orgQuery = Organize::find()->orderBy(['org_id' => SORT_ASC]);
        if ($user && $user->position != 4 && !empty($sessionOrg)) {
            $orgQuery->andWhere(['org_id' => $sessionOrg]);
        }
        $orgs = $orgQuery->all();

        foreach ($orgs as $org) {
            $q = Researchpro::find()->where(['org_id' => $org->org_id]);

            if ($isSelfRole) {
                $q->andWhere(['uid' => $user->uid]);
            }

            $countOrg = (int) $q->count();

            $seriesO[]     = $countOrg;
            $categoriesO[] = $org->org_name;
        }

        /* =========================================================
         * 3. นับกล่องบน (4 ตัว)
         *    อิงจาก researchTypeID 1-4
         * ========================================================= */
        if ($isSelfRole) {
            $uid = $user->uid;

            $counttype1 = Researchpro::find()->where(['uid' => $uid, 'researchTypeID' => 1])->count();
            $counttype2 = Researchpro::find()->where(['uid' => $uid, 'researchTypeID' => 2])->count();
            $counttype3 = Researchpro::find()->where(['uid' => $uid, 'researchTypeID' => 3])->count();
            $counttype4 = Researchpro::find()->where(['uid' => $uid, 'researchTypeID' => 4])->count();

            $countuser  = trim($user->uname . ' ' . $user->luname);
        } else {
            $counttype1 = Researchpro::find()->where(['researchTypeID' => 1])->count();
            $counttype2 = Researchpro::find()->where(['researchTypeID' => 2])->count();
            $counttype3 = Researchpro::find()->where(['researchTypeID' => 3])->count();
            $counttype4 = Researchpro::find()->where(['researchTypeID' => 4])->count();

            $countuser = Account::find()->count();
        }

        /* =========================================================
         * 4. สรุป 5 ประเด็นหลัก
         *    1) budgets
         *    2) researchTypeID
         *    3) researchFundID
         *    4) jobStatusID
         *    5) fundingAgencyID
         * ========================================================= */

        // query ตั้งต้นที่ถูกกรองสิทธิ์แล้ว
        $baseQuery = Researchpro::find();
        if ($isSelfRole) {
            $baseQuery->andWhere(['uid' => $user->uid]);
        } else {
            if ($user && $user->position != 4) {
                if (!empty($sessionOrg)) {
                    $baseQuery->andWhere(['org_id' => $sessionOrg]);
                } elseif (!empty($user->org_id)) {
                    $baseQuery->andWhere(['org_id' => $user->org_id]);
                }
            }
        }

        // 4.1 รวมงบประมาณ
        $totalBudgets = (int) (clone $baseQuery)->sum('budgets');

        // 4.2 ประเภทโครงการ
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

        // 4.3 ประเภทการวิจัย
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

        // 4.4 สถานะงาน
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

        // 4.5 แหล่งทุน
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

        // ===== ดึงชื่อ master สำหรับ map id → ชื่อ =====
        $restypeMap   = Restype::find()
            ->select(['restypeid', 'restypename'])
            ->indexBy('restypeid')
            ->asArray()
            ->all();

        $resfundMap   = ResFund::find()
            ->select(['researchFundID', 'researchFundName'])
            ->indexBy('researchFundID')
            ->asArray()
            ->all();

        $resstatusMap = Resstatus::find()
            ->select(['statusid', 'statusname'])
            ->indexBy('statusid')
            ->asArray()
            ->all();

        $agencyMap    = ResGency::find()
            ->select(['fundingAgencyID', 'fundingAgencyName'])
            ->indexBy('fundingAgencyID')
            ->asArray()
            ->all();

        return $this->render('index', [
            // กราฟปี
            'seriesY'     => $seriesY,
            'categoriesY' => $categoriesY,

            // กราฟหน่วยงาน
            'seriesO'     => $seriesO,
            'categoriesO' => $categoriesO,

            // box บน
            'counttype1'  => $counttype1,
            'counttype2'  => $counttype2,
            'counttype3'  => $counttype3,
            'counttype4'  => $counttype4,
            'countuser'   => $countuser,

            // สิทธิ์
            'isSelfRole'  => $isSelfRole,

            // รายงาน 5 ประเด็น
            'totalBudgets' => $totalBudgets,
            'typeData'     => $typeData,
            'fundData'     => $fundData,
            'statusData'   => $statusData,
            'agencyData'   => $agencyData,

            // map ชื่อ
            'restypeMap'   => $restypeMap,
            'resfundMap'   => $resfundMap,
            'resstatusMap' => $resstatusMap,
            'agencyMap'    => $agencyMap,
        ]);
    }
}
