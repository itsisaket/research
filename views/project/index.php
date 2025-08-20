<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ProjectSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Projects';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-index">
    <p>
        <?= Html::a('เพิ่มข้อมูลงานวิจัยใหม่', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php  echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
              'attribute' => 'การจัดการ',
              'format'=>'raw',
              'value'=>function($model){
                return  Html::a('ข้อมูล', ['view', 'id' => $model->pro_id], ['class' => 'btn btn-secondary']);     
              }
            ],          
            [
                'attribute' => 'pro_name',
                'value'=>function($model){
                  return $model->pro_name;
                }
              ],
              [
                'attribute' => 'uid',
                'value'=>function($model){
                  return $model->user->uname.' '.$model->user->luname;
                }
              ],            
              [
                'attribute' => 'pro_capital',
                'value'=>function($model){
                  return $model->capital->capitalname;
                }
              ],
              [
                'attribute' => 'pro_year',
                'value'=>function($model){
                  return $model->pro_year;
                }
            ],            

        ],
    ]); ?>


</div>
