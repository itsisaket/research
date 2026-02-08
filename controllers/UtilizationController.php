<?php

namespace app\controllers;

use Yii;
use app\models\Utilization;
use app\models\UtilizationSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use yii\helpers\ArrayHelper;

use app\models\Amphur;
use app\models\District;
use app\components\HanumanRule;

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
                        'actions' => ['index', 'error', 'view'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => ['get-amphur', 'get-district'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                    [
                        'actions' => ['create', 'update','delete'],
                        'allow'   => true,
                        'roles'   => [1, 4],
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'       => ['POST'],
                    'get-amphur'   => ['POST'],
                    'get-district' => ['POST'],
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

        $amphur = [];
        $subdistrict = [];

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {

                if ($model->save()) {
                    return $this->redirect(['view', 'utilization_id' => $model->utilization_id]);
                }

                // save ไม่ผ่าน → เติม list กลับให้ DepDrop
                if (!empty($model->province)) {
                    $amphur = ArrayHelper::map($this->getAmphur($model->province), 'id', 'name');
                }
                if (!empty($model->district)) {
                    $subdistrict = ArrayHelper::map($this->getDistrict($model->district), 'id', 'name');
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model'       => $model,
            'amphur'      => $amphur,
            'subdistrict' => $subdistrict,
        ]);
    }

    public function actionUpdate($utilization_id)
    {
        $model = $this->findModel($utilization_id);

        $amphur = !empty($model->province)
            ? ArrayHelper::map($this->getAmphur($model->province), 'id', 'name')
            : [];

        $subdistrict = !empty($model->district)
            ? ArrayHelper::map($this->getDistrict($model->district), 'id', 'name')
            : [];

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'utilization_id' => $model->utilization_id]);
        }

        return $this->render('update', [
            'model'       => $model,
            'amphur'      => $amphur,
            'subdistrict' => $subdistrict,
        ]);
    }

    public function actionDelete($utilization_id)
    {
        $model = $this->findModel($utilization_id);

        $me = Yii::$app->user->identity ?? null;
        $isOwner = ($me && (string)$me->username === (string)$model->username);
        $isAdmin = ($me && (int)$me->position === 4);

        if (!$isOwner && !$isAdmin) {
            throw new \yii\web\ForbiddenHttpException('คุณไม่มีสิทธิ์ลบรายการนี้');
        }

        $model->delete();
        return $this->redirect(['index']);
    }


    protected function findModel($utilization_id)
    {
        if (($model = Utilization::findOne(['utilization_id' => $utilization_id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /* ===================== DepDrop AJAX ===================== */

    public function actionGetAmphur()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (isset($_POST['depdrop_parents'])) {
            $parents = $_POST['depdrop_parents'];
            $province_id = $parents[0] ?? null;

            if ($province_id) {
                return ['output' => $this->getAmphur($province_id), 'selected' => ''];
            }
        }
        return ['output' => [], 'selected' => ''];
    }

    public function actionGetDistrict()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (isset($_POST['depdrop_parents'])) {
            $ids = $_POST['depdrop_parents'];
            $amphur_id = $ids[1] ?? null; // depends = province, amphur

            if ($amphur_id) {
                return ['output' => $this->getDistrict($amphur_id), 'selected' => ''];
            }
        }
        return ['output' => [], 'selected' => ''];
    }

    /* ===================== Helper สำหรับ DepDrop ===================== */

    protected function getAmphur($provinceId)
    {
        $datas = Amphur::find()
            ->where(['PROVINCE_ID' => $provinceId])
            ->orderBy(['AMPHUR_NAME' => SORT_ASC])
            ->all();

        return $this->mapData($datas, 'AMPHUR_CODE', 'AMPHUR_NAME');
    }

    protected function getDistrict($amphurId)
    {
        $datas = District::find()
            ->where(['AMPHUR_ID' => $amphurId])
            ->orderBy(['DISTRICT_NAME' => SORT_ASC])
            ->all();

        return $this->mapData($datas, 'DISTRICT_CODE', 'DISTRICT_NAME');
    }

    protected function mapData($datas, $fieldId, $fieldName)
    {
        $obj = [];
        foreach ($datas as $value) {
            $id   = is_array($value) ? ($value[$fieldId] ?? null) : ($value->{$fieldId} ?? null);
            $name = is_array($value) ? ($value[$fieldName] ?? null) : ($value->{$fieldName} ?? null);

            if ($id !== null) {
                $obj[] = ['id' => $id, 'name' => $name];
            }
        }
        return $obj;
    }
}
