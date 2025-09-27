<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Project */

$this->title = "";
$this->params['breadcrumbs'][] = ['label' => 'Projects', 'url' => ['index']];

\yii\web\YiiAsset::register($this);
?>
<div class="project-view">

    <p> 
        <?= Html::a('กลับหน้าหลักงานวิจัย', ['index'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-edit"></i> แก้ไขข้อมูล', ['update', 'id' => $model->pro_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-trash"></i> ลบข้อมูล', ['delete', 'id' => $model->pro_id], [
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
            'pro_name',
            [
                'attribute' => 'uid',
                'value'=>function($model){
                  return $model->user->uname.' '.$model->user->luname;
                }
              ], 
            [
                'attribute' => 'pro_position',
                'value'=>function($model){
                  return $model->position->res_positionname;
                }
            ],            
            [
                'attribute' => 'pro_capital',
                'value'=>function($model){
                  return $model->capital->capitalname;
                }
            ],
            [
                'attribute' => 'pro_type',
                'value'=>function($model){
                  return $model->restype->restypename;
                }
            ],
            [
                'attribute' => 'pro_year',
                'value'=>function($model){
                  return $model->pro_year;
                }
            ],
            'pro_budget',
            [
                'attribute' => 'pro_status',
                'value'=>function($model){
                  return $model->resstatus->statusname;
                }
            ],
            'pro_location',
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
            'dayup',
        ],
    ]) ?>

</div>
