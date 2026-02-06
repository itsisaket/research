<?php

namespace app\controllers;

use Yii;
use app\models\AcademicService;
use app\models\AcademicServiceSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class AcademicServiceController extends Controller
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
                        'actions' => ['index', 'view'],
                        'allow'   => true,
                        'roles'   => ['?', '@'], // ทุกคนดูได้
                    ],
                    [
                        'actions' => ['create', 'update', 'delete'],
                        'allow'   => true,
                        'roles'   => ['@'], // ต้องล็อกอินก่อนทำรายการ
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
        $searchModel = new AcademicServiceSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($service_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($service_id),
        ]);
    }

    public function actionCreate()
    {
        $model = new AcademicService();
        $model->loadDefaultValues();

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'service_id' => $model->service_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($service_id)
    {
        $model = $this->findModel($service_id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'service_id' => $model->service_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($service_id)
    {
        $model = $this->findModel($service_id);

        // ✅ ลบเฉพาะเจ้าของเรื่อง (username)
        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

        if (!$isOwner) {
            throw new \yii\web\ForbiddenHttpException('คุณไม่มีสิทธิ์ลบรายการนี้');
        }

        $model->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = AcademicService::findOne(['service_id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('ไม่พบข้อมูลที่ร้องขอ');
    }
}
