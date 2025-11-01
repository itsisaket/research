<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use app\models\Researchpro;
use app\models\Account;
use app\models\Organize;

class ReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        // ✅ ให้ดูรายงานได้เลย
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

            if ($user && ($user->position == 1 || $user->position == 2)) {
                $isSelfRole = true;
            }

        /* =========================================================
         * 1. กราฟรายปี (5 ปีย้อนหลัง) จาก tb_researchpro.projectYearsubmit
         * ========================================================= */
        $seriesY     = [];
        $categoriesY = [];

        // ปีปัจจุบัน (ค.ศ.) -> แปลงเป็น พ.ศ.
        $currentYearAD = (int) date('Y');
        $currentYearTH = $currentYearAD + 543;

        // เตรียม 5 ปีล่าสุด (รวมปีนี้)
        $yearsTH = [];
        for ($i = 0; $i < 5; $i++) {
            $yearsTH[] = $currentYearTH - $i;
        }
        // เรียงจากน้อย -> มาก เพื่อให้กราฟสวย
        $yearsTH = array_reverse($yearsTH);

        foreach ($yearsTH as $yearTH) {
            $query = Researchpro::find()
                ->where(['projectYearsubmit' => $yearTH]);

            // กรองตามสิทธิ์
            if ($isSelfRole) {
                // เห็นเฉพาะของตัวเอง
                $query->andWhere(['uid' => $user->uid]);
            } else {
                // ไม่ใช่สิทธิ์สูงสุด แล้วมี org ใน session → กรองตาม org นั้น
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
         * 2. กราฟแยกตามหน่วยงาน (org_id)
         * ========================================================= */
        $seriesO     = [];
        $categoriesO = [];

        // ดึงรายชื่อหน่วยงานทั้งหมด
        $orgQuery = Organize::find()->orderBy(['org_id' => SORT_ASC]);
        if ($user && $user->position != 4 && !empty($sessionOrg)) {
            // ถ้าไม่ใช่ admin → เห็นแค่ของตัวเอง
            $orgQuery->andWhere(['org_id' => $sessionOrg]);
        }
        $orgs = $orgQuery->all();

        foreach ($orgs as $org) {
            $query = Researchpro::find()->where(['org_id' => $org->org_id]);

            // ถ้าดูแบบสิทธิ์ตัวเอง (1,2) → เห็นเฉพาะที่ตัวเองเป็นหัวหน้า
            if ($isSelfRole) {
                $query->andWhere(['uid' => $user->uid]);
            }

            $countOrg = (int) $query->count();

            $seriesO[]     = $countOrg;
            $categoriesO[] = $org->org_name;
        }
        /* =========================================================
        * 3) นับประเภทโครงการ (ของเดิม)
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
        * 4) 🔥 ส่วนใหม่: รายงาน 4 ประเด็น
        *     - budgets (รวมงบ)
        *     - researchTypeID (แจกแจง)
        *     - researchFundID (แจกแจง)
        *     - jobStatusID (แจกแจง)
        *     ทั้งหมดต้อง “กรองสิทธิ์” แบบเดียวกับด้านบน
        * ========================================================= */

        // 4.1 รวมงบประมาณ
        $budgetQuery = Researchpro::find();
        if ($isSelfRole) {
            $budgetQuery->andWhere(['uid' => $user->uid]);
        } else {
            if ($user && $user->position != 4) {
                if (!empty($sessionOrg)) {
                    $budgetQuery->andWhere(['org_id' => $sessionOrg]);
                } elseif (!empty($user->org_id)) {
                    $budgetQuery->andWhere(['org_id' => $user->org_id]);
                }
            }
        }
        // ถ้า budgets เก็บเป็น int ธรรมดา ใช้ sum ได้เลย
        $totalBudgets = (int) $budgetQuery->sum('budgets');

        // 4.2 แจกแจงตาม researchTypeID
        // จะได้เป็น array แบบ [1 => 10, 2 => 5, ...]
        $typeData = [];
        $typeRows = (clone $budgetQuery)
            ->select(['researchTypeID', 'cnt' => 'COUNT(*)'])
            ->groupBy('researchTypeID')
            ->orderBy('researchTypeID')
            ->asArray()
            ->all();
        foreach ($typeRows as $row) {
            $typeData[$row['researchTypeID']] = (int) $row['cnt'];
        }

        // 4.3 แจกแจงตาม researchFundID
        $fundData = [];
        $fundRows = (clone $budgetQuery)
            ->select(['researchFundID', 'cnt' => 'COUNT(*)'])
            ->groupBy('researchFundID')
            ->orderBy('researchFundID')
            ->asArray()
            ->all();
        foreach ($fundRows as $row) {
            $fundData[$row['researchFundID']] = (int) $row['cnt'];
        }

        // 4.4 แจกแจงตาม jobStatusID
        $statusData = [];
        $statusRows = (clone $budgetQuery)
            ->select(['jobStatusID', 'cnt' => 'COUNT(*)'])
            ->groupBy('jobStatusID')
            ->orderBy('jobStatusID')
            ->asArray()
            ->all();
        foreach ($statusRows as $row) {
            $statusData[$row['jobStatusID']] = (int) $row['cnt'];
        }

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

            // ✅ ส่ง 4 ประเด็นใหม่ไปให้ view
            'totalBudgets' => $totalBudgets,
            'typeData'     => $typeData,
            'fundData'     => $fundData,
            'statusData'   => $statusData,
        ]);
    }
}