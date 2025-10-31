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

class ResearchproController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // ✅ เปิด index, error, และ ajax ของ DepDrop
                    [
                        'actions' => ['index', 'error', 'get-amphur', 'get-district'],
                        'allow' => true,
                    ],
                    // ✅ ส่วนที่เหลือต้องล็อกอิน
                    [
                        'actions' => ['view', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
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

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
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
}
