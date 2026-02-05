<?php

namespace app\controllers;

use Yii;
use app\models\Utilization;
use app\models\UtilizationSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use yii\helpers\Json;
use yii\helpers\ArrayHelper;

use app\models\Province;
use app\models\Amphur;
use app\models\District;

class UtilizationController extends Controller
{

public function behaviors()
{
    return [
        'access' => [
            'class' => AccessControl::class,
            'ruleConfig' => [
                'class' => \app\components\HanumanRule::class,
            ],
            'rules' => [
                [
                    'actions' => ['index', 'error'],
                    'allow'   => true,
                    'roles'   => ['?', '@'],
                ],
                // ✅ position 1 researcher + 4 admin
                [
                    'actions' => ['view', 'create', 'update'],
                    'allow'   => true,
                    'roles'   => [1, 4],
                ],
                // ✅ admin เท่านั้น
                [
                    'actions' => ['delete'],
                    'allow'   => true,
                    'roles'   => [4],
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
        $ty = $session->get('ty');

        $searchModel = new UtilizationSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        // filter ตาม org ถ้าล็อกอิน
        if (!Yii::$app->user->isGuest && $ty) {
            $dataProvider->query->andWhere(['org_id' => $ty]);
        }

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($utilization_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($utilization_id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Utilization();

        // ค่าเริ่มต้นให้ DepDrop
        $amphur = [];
        $sub_district = [];

        if ($this->request->isPost) {

            if ($model->load($this->request->post())) {

                // ถ้าจะล็อก org_id ตาม session ให้เปิดบรรทัดนี้
                // $model->org_id = Yii::$app->session->get('ty');

                if ($model->save()) {
                    return $this->redirect(['view', 'utilization_id' => $model->utilization_id]);
                }

                // ถ้าบันทึกไม่ผ่าน → โหลดอำเภอ/ตำบลกลับให้
                if ($model->province) {
                    $amphur = ArrayHelper::map($this->getAmphur($model->province), 'id', 'name');
                }
                if ($model->district) {
                    $sub_district = ArrayHelper::map($this->getDistrict($model->district), 'id', 'name');
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model'        => $model,
            'amphur'       => $amphur,
            'sub_district' => $sub_district,
        ]);
    }

    public function actionUpdate($utilization_id)
    {
        $model = $this->findModel($utilization_id);

        // โหลดค่าจังหวัด-อำเภอ-ตำบลเดิมเข้า DepDrop
        $amphur       = ArrayHelper::map($this->getAmphur($model->province), 'id', 'name');
        $sub_district = ArrayHelper::map($this->getDistrict($model->district), 'id', 'name');

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'utilization_id' => $model->utilization_id]);
        }

        return $this->render('update', [
            'model'        => $model,
            'amphur'       => $amphur,
            'sub_district' => $sub_district,
        ]);
    }

    public function actionDelete($utilization_id)
    {
        $this->findModel($utilization_id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($utilization_id)
    {
        if (($model = Utilization::findOne(['utilization_id' => $utilization_id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /* ============ AJAX for DepDrop ============ */

    public function actionGetAmphur()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (isset($_POST['depdrop_parents'])) {
            $parents = $_POST['depdrop_parents'];
            if ($parents != null) {
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

        if (isset($_POST['depdrop_parents'])) {
            $parents     = $_POST['depdrop_parents'];
            $province_id = $parents[0] ?? null;
            $amphur_id   = $parents[1] ?? null;

            if ($amphur_id !== null) {
                $data = $this->getDistrict($amphur_id);
                return ['output' => $data, 'selected' => ''];
            }
        }
        return ['output' => '', 'selected' => ''];
    }

    /* ============ helpers ============ */

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
            $obj[] = ['id' => $value->{$fieldId}, 'name' => $value->{$fieldName}];
        }
        return $obj;
    }
}
