<?php

namespace app\controllers;
use Yii;

use app\models\Researchpro;
use app\models\ResearchproSearch;
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
 * ResearchproController implements the CRUD actions for Researchpro model.
 */
class ResearchproController extends Controller
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
                    'delete' => ['POST'], // delete ต้องส่งแบบ POST เท่านั้น
                ],
            ],
        ];
    }

    /**
     * Lists all Researchpro models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new ResearchproSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Researchpro model.
     * @param int $projectID รหัสโครงการ
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($projectID)
    {
        return $this->render('view', [
            'model' => $this->findModel($projectID),
        ]);
    }

    /**
     * Creates a new Researchpro model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
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

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Researchpro model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $projectID รหัสโครงการ
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($projectID)
    {
        $model = $this->findModel($projectID);
        $amphur         = ArrayHelper::map($this->getAmphur($model->province),'id','name');
        $subdistrict       = ArrayHelper::map($this->getDistrict($model->district),'id','name');

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'projectID' => $model->projectID]);
        }

        return $this->render('update', [
            'model' => $model,
            'amphur'=> $amphur,
            'sub_district' => $subdistrict
        ]);
    }

    /**
     * Deletes an existing Researchpro model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $projectID รหัสโครงการ
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($projectID)
    {
        $this->findModel($projectID)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Researchpro model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $projectID รหัสโครงการ
     * @return Researchpro the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($projectID)
    {
        if (($model = Researchpro::findOne(['projectID' => $projectID])) !== null) {
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
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $ids = $_POST['depdrop_parents'];
            $province_id = empty($ids[0]) ? null : $ids[0];
            $amphur_id = empty($ids[1]) ? null : $ids[1];
            if ($province_id != null) {
               $data = $this->getDistrict($amphur_id);
               echo Json::encode(['output'=>$data, 'selected'=>'']);
               return;
            }
        }
        echo Json::encode(['output'=>'', 'selected'=>'']);
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
