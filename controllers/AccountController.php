<?php

namespace app\controllers;


use Yii;
use yii\db\Expression;
use app\models\Account;
use app\models\AccountSearch;
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

use yii\data\ActiveDataProvider;

/**
 * AccountController implements the CRUD actions for Account model.
 */
class AccountController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'index', 'view', 'create', 'update'],
                'rules' => [
                    // ✅ อนุญาตให้ทุกคนเข้า index ได้
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['?', '@'], // '?' = guest, '@' = authenticated
                    ],
                    // ✅ ต้องล็อกอินถึงเข้า action อื่นๆ ได้
                    [
                        'actions' => ['logout', 'view', 'create', 'update'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Account models.
     * @return mixed
     */
    public function actionIndex()
    {
        $session = Yii::$app->session;
        $ty=$session['ty'];

        $searchModel = new AccountSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
     
            $dataProvider->query->andWhere(['org_id'=>$ty]);

        

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,

        ]);
    }

    /**
     * Displays a single Account model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Account model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
            $model = new Account();
                if ($model->load(Yii::$app->request->post())) {
                    $key = 'aepoiq342y234iuhalncsalkhlkshkj';
                    $model->password = $this->enc_encrypt($model->password,$key);

                    if(($u=Account::find()->where(['username'=>$model->username])->one()) !== null) {
                        Yii::$app->session->setFlash('error', 'ขออภัย!! Username:'.$model->username.' ตรวจพบข้อมูลซ้ำในระบบ กรุณาลงทะเบียนใหม่ครับ');
                    }else{
                        $model->save();
                        Yii::$app->session->setFlash('success', 'ยินดีต้อนรับ '.$model->username.' ลงทะเบียนในระบบเรียบร้อย');
                    }
                            return $this->redirect(['index','ty' => $session['ty'],]);
                    
                }
            
            return $this->renderAjax('create', [
                'model' => $model,
            ]);
    }

    public function actionRegis()
    {
            $model = new Account();
                if ($model->load(Yii::$app->request->post())) {
                    $key = 'aepoiq342y234iuhalncsalkhlkshkj';
                    $model->password = $this->enc_encrypt($model->password,$key);
                    // return $this->redirect(['view', 'id' => $model->uid]);
                    if(($u=Account::find()->where(['username'=>$model->username])->one()) !== null) {
                        Yii::$app->session->setFlash('error', 'ขออภัย!! Username:'.$model->username.'ตรวจพบข้อมูลซ้ำในระบบ กรุณาลงทะเบียนใหม่ครับ');
                    }else{
                        $model->save();
                        Yii::$app->session->setFlash('success', 'ยินดีต้อนรับ '.$model->username.' ลงทะเบียนในระบบเรียบร้อย');
                    }

                        if (Yii::$app->user->isGuest) {
                            return $this->redirect(['site/login']);
                        }
                            return $this->redirect(['index']);
                    
                }
            
            return $this->renderAjax('regis', [
                'model' => $model,
            ]);
    }
    //echo enc_encrypt($string, $key)."\n";
    //echo enc_decrypt(enc_encrypt($string, $key), $key)."\n";
    function enc_encrypt($string, $key) {
        $result = '';
        for($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
    
        return base64_encode($result);
    }

    /**
     * Updates an existing Account model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post())) {
            $key = 'aepoiq342y234iuhalncsalkhlkshkj';
            $model->password = $this->enc_encrypt($model->password,$key);
            $model->save();
            Yii::$app->session->setFlash('success', 'รหัสผ่านของ'.$model->username.' แก้ไขในระบบเรียบร้อย');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Account model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Account model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Account the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Account::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionResetpassword($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $key = 'aepoiq342y234iuhalncsalkhlkshkj';
            $model->password = $this->enc_encrypt($model->password,$key);
            $model->save();
            Yii::$app->session->setFlash('success', 'รหัสผ่านของ'.$model->username.' แก้ไขในระบบเรียบร้อย');
            return $this->redirect(['view', 'id' => $model->uid]);
        }
        $model->password=NULL;
        return $this->renderAjax('resetpassword',['model' => $model]);
    }
 
    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout($ty)
    {
        Yii::$app->user->logout();
        $session = Yii::$app->session;
        $session['add_ty']=$ty;

        return $this->goHome();
        //return $this->redirect(['/site/index']);
    }
}
