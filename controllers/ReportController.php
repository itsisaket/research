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
                        'roles'   => ['@', '?'], // @ = login แล้ว, ? = guest
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

        // สมมติให้ position 1,2 เห็นเฉพาะของตัวเอง
        if ($user && ($user->position == 1 || $user->position == 2)) {
            $isSelfRole = true;
        }

        /* =========================================================
         * 1. กราฟ 5 ปีย้อนหลัง (จำนวนโครงการ + งบประมาณรายปี)
         * ========================================================= */
        $seriesY        = [];   // จำนวนโครงการรายปี
        $budgetSeriesY  = [];   // งบประมาณรวมรายปี
        $categoriesY    = [];   // ปี พ.ศ.

        $currentYearAD = (int) date('Y');
        $currentYearTH = $currentYearAD + 543;

        // เตรียม 5 ปีย้อนหลัง (รวมปีนี้)
        $yearsTH = [];
        for ($i = 0; $i < 5; $i++) {
            $yearsTH[] = $currentYearTH - $i;
        }
        // เรียงจากเก่า -> ใหม่
        $yearsTH = array_reverse($yearsTH);   // ex: [2564, 2565, 2566, 2567, 2568]

        foreach ($yearsTH as $yearTH) {
            // query ตั้งต้นของปีนั้น
            $q = Researchpro::find()->where(['projectYearsubmit' => $yearTH]);

            // กรองสิทธิ์
            if ($isSelfRole) {
                $q->andWhere(['username' => $user->username]);
            } else {
                // ไม่ใช่ admin แล้วมี org → กรองตาม org
                if ($user && $user->position != 4) {
                    if (!empty($sessionOrg)) {
                        $q->andWhere(['org_id' => $sessionOrg]);
                    } elseif (!empty($user->org_id)) {
                        $q->andWhere(['org_id' => $user->org_id]);
                    }
                }
            }

            // 1.1 จำนวนโครงการรายปี
            $countProject = (int) (clone $q)->count();

            // 1.2 งบประมาณรวมรายปี
            $sumBudgetYear = (int) (clone $q)->sum('budgets');

            $seriesY[]       = $countProject;
            $budgetSeriesY[] = $sumBudgetYear;
            $categoriesY[]   = (string) $yearTH;
        }

        /* =========================================================
         * 2. กราฟแยกตามหน่วยงาน (จาก Organize)
         * ========================================================= */
        $seriesO     = [];
        $categoriesO = [];

        $orgQuery = Organize::find()->orderBy(['org_id' => SORT_ASC]);
        if ($user && $user->position != 4 && !empty($sessionOrg)) {
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
         * 3. นับกล่องบน (วิจัย/บทความ/แผนงาน/บริการ)
         * ========================================================= */
        if ($isSelfRole) {
            $username = $user->username;

            $counttype1 = Researchpro::find()->where(['username' => $username, 'researchTypeID' => 1])->count();
            $counttype2 = Researchpro::find()->where(['username' => $username, 'researchTypeID' => 2])->count();
            $counttype3 = Researchpro::find()->where(['username' => $username, 'researchTypeID' => 3])->count();
            $counttype4 = Researchpro::find()->where(['username' => $username, 'researchTypeID' => 4])->count();

            // บนสุดโชว์ชื่อคน login
            $countuser  = trim($user->uname . ' ' . $user->luname);
        } else {
            $counttype1 = Researchpro::find()->where(['researchTypeID' => 1])->count();
            $counttype2 = Researchpro::find()->where(['researchTypeID' => 2])->count();
            $counttype3 = Researchpro::find()->where(['researchTypeID' => 3])->count();
            $counttype4 = Researchpro::find()->where(['researchTypeID' => 4])->count();

            // นับผู้ใช้ทั้งหมด
            $countuser  = Account::find()->count();
        }

        /* =========================================================
         * 4. สรุป 5 ประเด็นหลัก (รวมทุกปีที่มองเห็น)
         *    - งบประมาณรวม
         *    - ประเภทโครงการ
         *    - ประเภทการวิจัย
         *    - สถานะงาน
         *    - แหล่งทุน
         * ========================================================= */
        $baseQuery = Researchpro::find();
        if ($isSelfRole) {
            $baseQuery->andWhere(['username' => $user->username]);
        } else {
            if ($user && $user->position != 4) {
                if (!empty($sessionOrg)) {
                    $baseQuery->andWhere(['org_id' => $sessionOrg]);
                } elseif (!empty($user->org_id)) {
                    $baseQuery->andWhere(['org_id' => $user->org_id]);
                }
            }
        }

        // 4.1 รวมงบทั้งหมด
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

        // 4.5 แหล่งทุน (รวม)
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
         * 5. แหล่งทุนรายปี (เฉพาะที่มีโครงการจริงในช่วง 5 ปี)
         *    → ส่งไปให้ view วาดกราฟ
         * ========================================================= */

        // ดึงชื่อแหล่งทุนทั้งหมดมาก่อน
        $agencyMap = ResGency::find()
            ->select(['fundingAgencyID', 'fundingAgencyName'])
            ->indexBy('fundingAgencyID')
            ->asArray()
            ->all();

        $fundingSeries        = [];  // สำหรับ Highcharts
        $fundingTotalNonZero  = [];  // สำหรับลิสต์ด้านข้าง

        // เอาเฉพาะแหล่งทุนที่มีโครงการจริง (จากการรวมทุกปี)
        $candidateAgencyIds = array_keys($agencyData);

        foreach ($candidateAgencyIds as $agencyId) {
            $dataPerYear      = [];
            $totalThisAgency  = 0;

            foreach ($yearsTH as $yearTH) {
                $aq = Researchpro::find()
                    ->where([
                        'projectYearsubmit' => $yearTH,
                        'fundingAgencyID'   => $agencyId,
                    ]);

                // กรองสิทธิ์
                if ($isSelfRole) {
                    $aq->andWhere(['username' => $user->username]);
                } else {
                    if ($user && $user->position != 4) {
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

            // เอาเฉพาะแหล่งทุนที่มีโครงการอย่างน้อย 1 โครงการใน 5 ปี
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

        // ===== map อื่น ๆ สำหรับแสดงชื่อใน view =====
        $restypeMap   = Restype::find()->select(['restypeid', 'restypename'])->indexBy('restypeid')->asArray()->all();
        $resfundMap   = ResFund::find()->select(['researchFundID', 'researchFundName'])->indexBy('researchFundID')->asArray()->all();
        $resstatusMap = Resstatus::find()->select(['statusid', 'statusname'])->indexBy('statusid')->asArray()->all();

        return $this->render('index', [
            // กราฟปี
            'seriesY'        => $seriesY,
            'budgetSeriesY'  => $budgetSeriesY,
            'categoriesY'    => $categoriesY,

            // กราฟหน่วยงาน
            'seriesO'        => $seriesO,
            'categoriesO'    => $categoriesO,

            // box บน
            'counttype1'     => $counttype1,
            'counttype2'     => $counttype2,
            'counttype3'     => $counttype3,
            'counttype4'     => $counttype4,
            'countuser'      => $countuser,

            'isSelfRole'     => $isSelfRole,

            // สรุป 5 ประเด็น
            'totalBudgets'   => $totalBudgets,
            'typeData'       => $typeData,
            'fundData'       => $fundData,
            'statusData'     => $statusData,
            'agencyData'     => $agencyData,

            // map ชื่อ
            'restypeMap'     => $restypeMap,
            'resfundMap'     => $resfundMap,
            'resstatusMap'   => $resstatusMap,
            'agencyMap'      => $agencyMap,

            // ✅ แหล่งทุนรายปี
            'fundingSeries'       => $fundingSeries,
            // ✅ ลิสต์แหล่งทุนที่มีโครงการจริง
            'fundingTotalNonZero' => $fundingTotalNonZero,
        ]);
    }
}
