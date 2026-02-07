<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UtilizationSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'การนำไปใช้ประโยชน์';

?>
<div class="utilization-index">


    <p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

  <?= $this->render('_search', [
    'model'    => $searchModel,
    'pubItems' => $pubItems,
]) ?>

<?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
              'attribute' => 'การจัดการ',
              'format'=>'raw',
              'value'=>function($model){
                return  Html::a('<i class="fas fa-eye"></i>', ['view', 'utilization_id' => $model->utilization_id], ['class' => 'btn btn-sm btn-outline-secondary']);   
                 
              }
            ],          
            [
                'attribute' => 'project_name',
                'value'=>function($model){
                  return $model->project_name;
                }
              ],
              [
                'attribute' => 'username',
                'value'=>function($model){
                  return $model->user->uname.' '.$model->user->luname;
                }
              ],    
              [
                'attribute' => 'org_id',
                'value'=>function($model){
                  return $model->hasorg->org_name;
                }
              ],                  
              [
                'attribute' => 'utilization_type',
                'value'=>function($model){
                  return $model->utilization->utilization_type_name;
                }
              ],                 

        ],
    ]); ?>
</div>
