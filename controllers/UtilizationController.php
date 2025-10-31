<?php

namespace app\controllers;

use Yii;
use app\models\Utilization;
use app\models\UtilizationSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\components\HanumanRule;
use app\models\User;

use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseFileHelper;
use yii\helpers\Html;
use yii\helpers\Url;

use app\models\Province;
use app\models\Amphur;
use app\models\District;

use  yii\web\Session;

/**
 * UtilizationController implements the CRUD actions for Utilization model.
 */
class UtilizationController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            // ✅ ป้องกันการเข้าถึงเฉพาะผู้ล็อกอิน
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // ✅ เปิดให้ทุกคนเข้าได้
                    [
                        'actions' => ['index','error'],
                        'allow' => true,
                    ],
                    // ✅ ต้องล็อกอิน
                    [
                        'actions' => ['view', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],

            // ✅ กำหนด HTTP Method ให้ชัด (ป้องกันเรียกผิด)
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'], 
    
                ],
            ],
        ];
    }

    /**
     * Lists all Utilization models.
     *
     * @return string
     */
    public function actionIndex()
    {   
        $session = Yii::$app->session;
        $ty=$session['ty'];

        $searchModel = new UtilizationSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        if (!Yii::$app->user->isGuest) {
             $dataProvider->query->andWhere(['org_id'=>$ty]);
        }

        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Utilization model.
     * @param int $utilization_id รหัส
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($utilization_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($utilization_id),
        ]);
    }

public function actionCreate()
{
    $model = new Utilization();

    $amphur = [];
    $sub_district = [];

    if ($this->request->isPost) {
        if ($model->load($this->request->post())) {

            // ถ้าจะล็อก org_id ตาม session
            // $model->org_id = Yii::$app->session->get('ty');

            if ($model->save()) {
                return $this->redirect(['view', 'utilization_id' => $model->utilization_id]);
            }

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


    /**
     * Deletes an existing Utilization model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $utilization_id รหัส
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($utilization_id)
    {
        $this->findModel($utilization_id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Utilization model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $utilization_id รหัส
     * @return Utilization the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($utilization_id)
    {
        if (($model = Utilization::findOne(['utilization_id' => $utilization_id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
     
    public function actionGetAmphur() {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $parents = $_POST['depdrop_parents'];
            if ($parents != null) {
                $province_id = $parents[0];
                $out = $this->getAmphur($province_id);
                echo Json::encode(['output'=>$out, 'selected'=>'']);
                return;
            }
        }
        echo Json::encode(['output'=>'', 'selected'=>'']);
    }
public function actionGetDistrict() {
    if (isset($_POST['depdrop_parents'])) {
        $parents = $_POST['depdrop_parents'];
        $provinceId = $parents[0] ?? null;
        $amphurId   = $parents[1] ?? null;

        if ($amphurId !== null) {
            $data = $this->getDistrict($amphurId);
            return $this->asJson(['output' => $data, 'selected' => '']);
        }
    }
    return $this->asJson(['output' => '', 'selected' => '']);
}

    protected function getAmphur($id){
        $datas = Amphur::find()->where(['PROVINCE_ID'=>$id])->all();
        return $this->MapData($datas,'AMPHUR_CODE','AMPHUR_NAME');
    }
    
    protected function getDistrict($id){
        $datas = District::find()->where(['AMPHUR_ID'=>$id])->all();
        return $this->MapData($datas,'DISTRICT_CODE','DISTRICT_NAME');
    }
    
    protected function MapData($datas,$fieldId,$fieldName){
        $obj = [];
        foreach ($datas as $key => $value) {
            array_push($obj, ['id'=>$value->{$fieldId},'name'=>$value->{$fieldName}]);
        }
        return $obj;
    } 
}
