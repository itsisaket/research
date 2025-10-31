<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use app\models\Project;
use app\models\Resyear;
use app\models\Resposition;
use app\models\Account;

/**
 * ReportController แสดงรายงานสรุปข้อมูลโครงการวิจัย
 */
class ReportController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            // ✅ กรองสิทธิ์ก่อน (กัน guest)
            'access' => [
                'class' => AccessControl::class,
                'only'  => ['index'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],   // ต้องล็อกอิน
                    ],
                ],
            ],

            // ✅ กันลบด้วย POST
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $user   = Yii::$app->user->identity;
        $isSelfRole = false;

        /**
         * กำหนด logic สิทธิ์แบบง่าย ๆ
         * - ถ้า position == 1 หรือ 2 → ให้เห็นเฉพาะของตัวเอง
         * - อื่น ๆ → ให้เห็นรวมทั้งระบบ
         *
         * ตรงนี้คุณปรับให้ตรงกับระบบจริงได้เลย เช่น
         * 1 = อาจารย์ / นักวิจัย
         * 2 = หัวหน้าหน่วย
         * 3,4,5 = admin
         */
        if ($user && ($user->position == 1 || $user->position == 2)) {
            $isSelfRole = true;
        }

        /* =========================================================
         * 1. ดึงข้อมูลรายปี
         * ========================================================= */
        $seriesY     = [];   // ข้อมูล Y (จำนวน)
        $categoriesY = [];   // แกน X (ปี)

        $modelResyear = Resyear::find()->orderBy(['resyear' => SORT_ASC])->all();

        foreach ($modelResyear as $resyear) {
            if ($isSelfRole) {
                // เห็นเฉพาะของตัวเอง
                $countProject = Project::find()
                    ->where(['uid' => $user->uid])
                    ->andWhere(['pro_year' => $resyear->resyear])
                    ->count();
            } else {
                // เห็นทั้งระบบ
                $countProject = Project::find()
                    ->where(['pro_year' => $resyear->resyear])
                    ->count();
            }

            // Highcharts column ต้องการ array ตัวเลขเรียบ ๆ เช่น [5,10,8]
            $seriesY[]     = (int) $countProject;
            $categoriesY[] = (string) $resyear->resyear;
        }

        /* =========================================================
         * 2. ดึงข้อมูล "ตำแหน่ง" (ในโค้ดเดิมใช้ Resposition)
         *    แต่ชื่อหัวข้อใน view เขียนว่า "ตามหน่วยงาน" อันนี้ผมยังคงโครงให้ก่อน
         * ========================================================= */
        $seriesO     = [];
        $categoriesO = [];

        $modelResposition = Resposition::find()->orderBy(['res_positionid' => SORT_ASC])->all();

        foreach ($modelResposition as $rp) {
            if ($isSelfRole) {
                $countResposition = Project::find()
                    ->where(['uid' => $user->uid])
                    ->andWhere(['pro_position' => $rp->res_positionid])
                    ->count();
            } else {
                $countResposition = Project::find()
                    ->where(['pro_position' => $rp->res_positionid])
                    ->count();
            }

            $seriesO[]     = (int) $countResposition;
            $categoriesO[] = (string) $rp->res_positionname;
        }

        /* =========================================================
         * 3. นับประเภทโครงการ
         *    pro_type:
         *    1 = โครงการวิจัย
         *    2 = ชุดแผนงาน
         *    3 = บริการวิชาการ
         *    4 = บทความวิจัย
         * ========================================================= */
        if ($isSelfRole) {
            $uid = $user->uid;

            $counttype1 = Project::find()->where(['uid' => $uid, 'pro_type' => 1])->count();
            $counttype2 = Project::find()->where(['uid' => $uid, 'pro_type' => 2])->count();
            $counttype3 = Project::find()->where(['uid' => $uid, 'pro_type' => 3])->count();
            $counttype4 = Project::find()->where(['uid' => $uid, 'pro_type' => 4])->count();

            // ชื่อผู้ใช้แสดงด้านข้าง
            $countuser  = trim($user->uname . ' ' . $user->luname);
        } else {
            $counttype1 = Project::find()->where(['pro_type' => 1])->count();
            $counttype2 = Project::find()->where(['pro_type' => 2])->count();
            $counttype3 = Project::find()->where(['pro_type' => 3])->count();
            $counttype4 = Project::find()->where(['pro_type' => 4])->count();

            // นับนักวิจัยทั้งหมด
            $countuser = Account::find()->count();
        }

        // ส่งไปที่ view
        return $this->render('index', [
            // กราฟปี
            'seriesY'     => $seriesY,
            'categoriesY' => $categoriesY,

            // กราฟตำแหน่ง / หน่วยงาน
            'seriesO'     => $seriesO,
            'categoriesO' => $categoriesO,

            // box ขวามือ
            'counttype1'  => $counttype1,
            'counttype2'  => $counttype2,
            'counttype3'  => $counttype3,
            'counttype4'  => $counttype4,
            'countuser'   => $countuser,

            // เอาไว้เผื่ออยากรู้ใน view
            'isSelfRole'  => $isSelfRole,
        ]);
    }
}
