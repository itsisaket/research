<?php

namespace app\controllers;

use app\models\Utilization_type;
use app\models\Utilization_typeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Utilization_typeController implements the CRUD actions for Utilization_type model.
 */
class Utilization_typeController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Utilization_type models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new Utilization_typeSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Utilization_type model.
     * @param int $utilization_type รหัส
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($utilization_type)
    {
        return $this->render('view', [
            'model' => $this->findModel($utilization_type),
        ]);
    }

    /**
     * Creates a new Utilization_type model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Utilization_type();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'utilization_type' => $model->utilization_type]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Utilization_type model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $utilization_type รหัส
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($utilization_type)
    {
        $model = $this->findModel($utilization_type);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'utilization_type' => $model->utilization_type]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Utilization_type model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $utilization_type รหัส
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($utilization_type)
    {
        $this->findModel($utilization_type)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Utilization_type model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $utilization_type รหัส
     * @return Utilization_type the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($utilization_type)
    {
        if (($model = Utilization_type::findOne(['utilization_type' => $utilization_type])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
