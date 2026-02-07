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
use app\models\User;

use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseFileHelper;
use yii\helpers\Html;
use yii\helpers\Url;

use yii\data\ActiveDataProvider;

// ✅ ปรับชื่อโมเดลตามโปรเจกต์คุณ
use app\models\Researchpro;
use app\models\Article;
use app\models\Utilization;
use app\models\AcademicService;



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
                'ruleConfig' => [
                    'class' => \app\components\HanumanRule::class,
                ],
                'rules' => [
                    [
                        'actions' => ['index', 'error'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => [
                            'view', 'create', 'update', 'delete',
                        ],
                        'allow'   => true,
                        'roles'   => [1, 4],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-contributor' => ['POST'],
                    'update-contributor-pct' => ['POST'],
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

        if (!empty($ty)) {
             $dataProvider->query->andWhere(['org_id'=>$ty]);
        }
       
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
    $model = $this->findModel($id);

    $classes = [
        'research' => \app\models\Researchpro::class,
        'article'  => \app\models\Article::class,
        'util'     => \app\models\Utilization::class,
        'service'  => \app\models\AcademicService::class,
    ];

    $cnt = [];
    $latest = [];

    foreach ($classes as $k => $cls) {
        $cond = $this->ownerCondition($cls, $model);
        $pk   = $this->pkField($cls);

        $cnt[$k] = (int)$cls::find()->where($cond)->count();
        $latest[$k] = $cls::find()->where($cond)->orderBy([$pk => SORT_DESC])->limit(5)->all();
    }

    return $this->render('view', [
        'model' => $model,
        'cntResearch' => $cnt['research'],
        'cntArticle'  => $cnt['article'],
        'cntUtil'     => $cnt['util'],
        'cntService'  => $cnt['service'],
        'researchLatest' => $latest['research'],
        'articleLatest'  => $latest['article'],
        'utilLatest'     => $latest['util'],
        'serviceLatest'  => $latest['service'],
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
        if (($model = Account::findOne((int)$id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('ไม่พบข้อมูลผู้ใช้ที่ต้องการ');
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
    private function ownerCondition($modelClass, $account)
    {
        // เลือกฟิลด์เจ้าของที่มีจริงก่อน
        $m = new $modelClass();
        if ($m->hasAttribute('username') && !empty($account->username)) {
            return ['username' => $account->username];
        }
        if ($m->hasAttribute('uid')) {
            return ['uid' => (int)$account->uid];
        }
        if ($m->hasAttribute('created_by')) {
            return ['created_by' => (int)$account->uid];
        }
        // ไม่รู้จะผูกด้วยอะไร → กัน error
        return ['0' => 1];
    }

    private function pkField($modelClass)
    {
        $pk = $modelClass::primaryKey();
        return $pk[0] ?? 'id';
    }
}
