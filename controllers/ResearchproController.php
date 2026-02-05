<?php

namespace app\controllers;

use Yii;
use app\models\Researchpro;
use app\models\ResearchproSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

use app\models\Amphur;
use app\models\District;

use yii\web\UploadedFile;
use app\models\ResearchImportForm;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ResearchproController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'ruleConfig' => [
                    'class' => \app\components\HanumanRule::class, // 👈 ใช้ HanumanRule
                ],
                'rules' => [
                    // ✅ public: ดู index, error, ajax ได้ทุกคน
                    [
                        'actions' => ['index', 'error'],
                        'allow'   => true,
                        'roles'   => ['?', '@'], // guest + login
                    ],

                    // ✅ เฉพาะ researcher (position = 1) + admin (position = 4) ดู view ได้
                    [
                        'actions' => ['view','create', 'update'],
                        'allow'   => true,
                        'roles'   => ['researcher', 'admin'],
                    ],

                    // ✅ เฉพาะ admin (position = 4) แก้ไข/ลบ/สร้างได้
                    [
                        'actions' => ['create', 'update', 'delete','import'],
                        'allow'   => true,
                        'roles'   => ['admin'],
                    ],
                ],
            ],
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
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $searchModel = new ResearchproSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        if (!Yii::$app->user->isGuest && $ty) {
            $dataProvider->query->andWhere(['org_id' => $ty]);
        }

        $importModel = new ResearchImportForm();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'importModel'  => $importModel,   // ✅ ส่งไปใช้ใน Modal
        ]);
    }

    public function actionView($projectID)
    {
        return $this->render('view', [
            'model' => $this->findModel($projectID),
        ]);
    }

    public function actionCreate()
    {
        $model = new Researchpro();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'projectID' => $model->projectID]);
            }
        } else {
            $model->loadDefaultValues();
        }

        // ⭐ ส่ง array ว่าง ๆ ไปให้ view เพื่อ DepDrop ตอน create
        return $this->render('create', [
            'model'        => $model,
            'amphur'       => [],
            'sub_district' => [],
        ]);
    }

    public function actionUpdate($projectID)
    {
        $model = $this->findModel($projectID);

        // ดึงอำเภอจากจังหวัดที่บันทึกไว้
        $amphur = [];
        if ($model->province) {
            $amphur = ArrayHelper::map($this->getAmphur($model->province), 'id', 'name');
        }

        // ดึงตำบลจากอำเภอที่บันทึกไว้
        $subdistrict = [];
        if ($model->district) {
            // ⚠️ ฟังก์ชัน getDistrict() ต้องการ AMPHUR_ID
            $subdistrict = ArrayHelper::map($this->getDistrict($model->district), 'id', 'name');
        }

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'projectID' => $model->projectID]);
        }

        return $this->render('update', [
            'model'        => $model,
            'amphur'       => $amphur,
            'sub_district' => $subdistrict,
        ]);
    }

    public function actionDelete($projectID)
    {
        $this->findModel($projectID)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($projectID)
    {
        if (($model = Researchpro::findOne(['projectID' => $projectID])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /* ===================== DepDrop AJAX ===================== */

    public function actionGetAmphur()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $parents = $_POST['depdrop_parents'];
            if (!empty($parents)) {
                $province_id = $parents[0];
                $out = $this->getAmphur($province_id);
                return ['output' => $out, 'selected' => ''];
            }
        }
        return ['output' => '', 'selected' => ''];
    }

    public function actionGetDistrict()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $ids         = $_POST['depdrop_parents'];
            $province_id = $ids[0] ?? null;
            $amphur_id   = $ids[1] ?? null;

            // ต้องมีอำเภอถึงจะโหลดตำบล
            if ($amphur_id) {
                $out = $this->getDistrict($amphur_id);
                return ['output' => $out, 'selected' => ''];
            }
        }
        return ['output' => '', 'selected' => ''];
    }

    /* ===================== Helper สำหรับ DepDrop ===================== */

    protected function getAmphur($provinceId)
    {
        $datas = Amphur::find()->where(['PROVINCE_ID' => $provinceId])->all();
        return $this->mapData($datas, 'AMPHUR_CODE', 'AMPHUR_NAME');
    }

    protected function getDistrict($amphurId)
    {
        $datas = District::find()->where(['AMPHUR_ID' => $amphurId])->all();
        return $this->mapData($datas, 'DISTRICT_CODE', 'DISTRICT_NAME');
    }

    protected function mapData($datas, $fieldId, $fieldName)
    {
        $obj = [];
        foreach ($datas as $value) {
            $obj[] = [
                'id'   => $value->{$fieldId},
                'name' => $value->{$fieldName},
            ];
        }
        return $obj;
    }
        
    public function actionImport()
    {
        $model = new ResearchImportForm();

        if (!Yii::$app->request->isPost) {
            return $this->redirect(['index']);
        }

        $model->file = UploadedFile::getInstance($model, 'file');

        if (!$model->validate()) {
            Yii::$app->session->setFlash('error', 'กรุณาเลือกไฟล์ Excel ให้ถูกต้อง');
            return $this->redirect(['index']);
        }

        // บันทึกไฟล์ชั่วคราว
        $tempPath = Yii::getAlias('@runtime') . '/import_researchpro_' . time() . '.' . $model->file->extension;
        $model->file->saveAs($tempPath);

        $rowsToSave = [];
        $rowErrors  = [];
        $rowsImported = 0;

        try {
            $spreadsheet = IOFactory::load($tempPath);
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestRow();

            /**
             * สมมติโครงคอลัมน์ Excel: แถวที่ 1 เป็นหัวตาราง
             * A: projectNameTH
             * B: projectNameEN
             * C: username
             * D: org_id
             * E: projectYearsubmit
             * F: budgets
             * G: fundingAgencyID
             * H: researchFundID
             * I: researchTypeID
             * J: projectStartDate
             * K: projectEndDate
             * L: jobStatusID
             * M: researchArea
             * N: sub_district
             * O: district
             * P: province
             * Q: branch
             * R: documentid (ถ้ามี)
             */

            // 1) loop ตรวจสอบก่อน ยังไม่ save
            for ($row = 2; $row <= $highestRow; $row++) {

                // เช็คว่าแถวนี้มีข้อมูลหรือเปล่า (ถ้าคอลัมน์แรกว่าง ข้าม)
                $projectNameTH = trim((string)$sheet->getCell('A' . $row)->getValue());
                if ($projectNameTH === '') {
                    continue;
                }

                $modelRow = new Researchpro();
                $modelRow->projectNameTH      = $projectNameTH;
                $modelRow->projectNameEN      = trim((string)$sheet->getCell('B' . $row)->getValue());
                $modelRow->username           = (int)$sheet->getCell('C' . $row)->getValue();
                $modelRow->org_id             = (int)$sheet->getCell('D' . $row)->getValue();
                $modelRow->projectYearsubmit  = (int)$sheet->getCell('E' . $row)->getValue();
                $modelRow->budgets            = (int)$sheet->getCell('F' . $row)->getValue();
                $modelRow->fundingAgencyID    = (int)$sheet->getCell('G' . $row)->getValue();
                $modelRow->researchFundID     = (int)$sheet->getCell('H' . $row)->getValue();
                $modelRow->researchTypeID     = (int)$sheet->getCell('I' . $row)->getValue();

                // แปลงวันที่
                $startDateRaw                 = $sheet->getCell('J' . $row)->getValue();
                $endDateRaw                   = $sheet->getCell('K' . $row)->getValue();
                $modelRow->projectStartDate   = $this->convertExcelDate($startDateRaw);
                $modelRow->projectEndDate     = $this->convertExcelDate($endDateRaw);

                $modelRow->jobStatusID        = (int)$sheet->getCell('L' . $row)->getValue();
                $modelRow->researchArea       = trim((string)$sheet->getCell('M' . $row)->getValue());
                $modelRow->sub_district       = (int)$sheet->getCell('N' . $row)->getValue();
                $modelRow->district           = (int)$sheet->getCell('O' . $row)->getValue();
                $modelRow->province           = (int)$sheet->getCell('P' . $row)->getValue();
                $modelRow->branch             = (int)$sheet->getCell('Q' . $row)->getValue();
                $modelRow->documentid         = trim((string)$sheet->getCell('R' . $row)->getValue());

                // ตรวจสอบตาม rules() ใน Researchpro
                if (!$modelRow->validate()) {
                    $rowErrors[$row] = $modelRow->getFirstErrors();
                } else {
                    $rowsToSave[] = $modelRow;
                }
            }

            // ถ้ามี error → ไม่บันทึกข้อมูล แจ้งแถวที่ผิด
            if (!empty($rowErrors)) {
                Yii::$app->session->setFlash('error', 'นำเข้าข้อมูลไม่สำเร็จ: พบข้อผิดพลาดในบางแถว');
                Yii::$app->session->setFlash('importErrors', $rowErrors);
            } else {
                // 2) ถ้าไม่มี error แถวไหนเลย → save ทั้งหมดใน transaction
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    foreach ($rowsToSave as $modelRow) {
                        if (!$modelRow->save(false)) {
                            throw new \Exception('บันทึกข้อมูลล้มเหลวในบางแถว');
                        }
                        $rowsImported++;
                    }
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', "นำเข้าข้อมูลสำเร็จ จำนวน {$rowsImported} แถว");
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดระหว่างบันทึกข้อมูล: ' . $e->getMessage());
                }
            }

        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดระหว่างอ่านไฟล์: ' . $e->getMessage());
        }

        // ลบไฟล์ชั่วคราว
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return $this->redirect(['index']);
    }

    /**
     * แปลงค่า date จาก Excel → 'Y-m-d'
     */
    protected function convertExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // ถ้าเป็นเลข serial ของ Excel
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string)$value);

        // ลอง d/m/Y
        $dt = \DateTime::createFromFormat('d/m/Y', $value);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }

        // ลอง Y-m-d
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }

        return null;
    }

}
