<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */

$this->title = 'การนำไปใช้ประโยชน์';

$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="utilization-view">


    <p> 
        <?= Html::a('ย้อนกลับ', ['index'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-edit"></i> แก้ไขข้อมูล', ['update', 'utilization_id' => $model->utilization_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-trash"></i> ลบข้อมูล', ['delete', 'utilization_id' => $model->utilization_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'project_name',
            [
                'attribute' => 'uid',
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
            [
                'attribute' => 'sub_district',
                'value'=>function($model){
                  return $model->dist->DISTRICT_NAME;
                }
            ],
            [
                'attribute' => 'district',
                'value'=>function($model){
                  return $model->amph->AMPHUR_NAME;
                }
            ],
            [
                'attribute' => 'province',
                'value'=>function($model){
                  return $model->prov->PROVINCE_NAME;
                }
            ],
            'utilization_date',
            'utilization_detail:ntext',
            'utilization_refer:ntext',
        ],
    ]) ?>
</div>
