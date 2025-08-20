<?php

namespace app\controllers;

use Yii;
use app\models\Project;
use app\models\ProjectSearch;
use app\models\Resyear;
use app\models\Capital;
use app\models\Account;
use app\models\Organize;
use app\models\Resposition;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseFileHelper;
use yii\helpers\Html;
use yii\helpers\Url;

use app\models\Province;
use app\models\Amphur;
use app\models\District;
/**
 * ProjectController implements the CRUD actions for Project model.
 */
class ReportController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        //ดึงข้อมูลรายปี
        $modelResyear = Resyear::find()->all();
        foreach($modelResyear as $Resyear){
            if(Yii::$app->user->identity->position==1 OR Yii::$app->user->identity->position==2){
                $countProject = Project::find()
                ->where(['uid'=>Yii::$app->user->identity->uid])
                ->andwhere(['pro_year'=>$Resyear['resyear']])
                ->all();
            }else{
                $countProject = Project::find()
                ->where(['pro_year'=>$Resyear['resyear']])
                ->all();
            }
                $seriesY[] = ['y' => count($countProject),];
                $categoriesY[] = [$Resyear['resyear']];
        }

        //ดึงข้อมูลตำแหน่ง
        $modelResposition = Resposition::find()->all();
        foreach($modelResposition as $Resposition){
            if(Yii::$app->user->identity->position==1 OR Yii::$app->user->identity->position==2){
                $countResposition = Project::find()
                    ->where(['uid'=>Yii::$app->user->identity->uid])
                    ->andwhere(['pro_position'=>$Resposition['res_positionid']])
                    ->all();
            }else{
                $countResposition = Project::find()
                    ->where(['pro_position'=>$Resposition['res_positionid']])
                    ->all();
            }
                $seriesO[] = ['y' => count($countResposition),];
                $categoriesO[] = [$Resposition['res_positionname']];                
        }

        $searchModel = Project::find()->all();
        if(Yii::$app->user->identity->position==1 OR Yii::$app->user->identity->position==2){
            $counttype1 = Project::find()->where(['uid'=>Yii::$app->user->identity->uid])->andwhere(['pro_type'=>1])->count();
            $counttype2 = Project::find()->where(['uid'=>Yii::$app->user->identity->uid])->andwhere(['pro_type'=>2])->count();
            $counttype3 = Project::find()->where(['uid'=>Yii::$app->user->identity->uid])->andwhere(['pro_type'=>3])->count();
            $counttype4 = Project::find()->where(['uid'=>Yii::$app->user->identity->uid])->andwhere(['pro_type'=>4])->count();
            $countuser = Yii::$app->user->identity->uname.' '.Yii::$app->user->identity->luname;

        }else{
            $counttype1 = Project::find()->where(['pro_type'=>1])->count();
            $counttype2 = Project::find()->where(['pro_type'=>2])->count();
            $counttype3 = Project::find()->where(['pro_type'=>3])->count();
            $counttype4 = Project::find()->where(['pro_type'=>4])->count();
            $countuser = Account::find()->count();
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'seriesY' => $seriesY,
            'categoriesY' => $categoriesY,
            'seriesO' => $seriesO,
            'categoriesO' => $categoriesO,
            'counttype1' => $counttype1,
            'counttype2' => $counttype2,
            'counttype3' => $counttype3,
            'counttype4' => $counttype4,
            'countuser' => $countuser,
        ]);
    }

}

