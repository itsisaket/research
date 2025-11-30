<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */

$this->title = 'โครงการวิจัย';

$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="researchpro-view">

<p> 
        <?= Html::a('ย้อนกลับ', ['index'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-edit"></i> แก้ไขข้อมูล', ['update', 'projectID' => $model->projectID], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-trash"></i> ลบข้อมูล', ['delete', 'projectID' => $model->projectID], [
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
            [
                'attribute' => 'projectID',
                'value'=>function($model){
                  return $model->projectID;
                }
            ],
            [
                'attribute' => 'projectNameTH',
                'value'=>function($model){
                  return $model->projectNameTH;
                }
            ],
            [
                'attribute' => 'projectNameEN',
                'value'=>function($model){
                  return $model->projectNameEN;
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
                'attribute' => 'projectYearsubmit',
                'value'=>function($model){
                  return $model->projectYearsubmit;
                }
            ],
            [
                'attribute' => 'budgets',
                'value'=>function($model){
                  return $model->budgets;
                }
            ],
            [
                'attribute' => 'fundingAgencyID',
                'value'=>function($model){
                  return $model->agencys->fundingAgencyName;
                }
            ],
            [
                'attribute' => 'researchFundID',
                'value'=>function($model){
                  return $model->resFunds->researchFundName;
                }
            ],           
            [
                'attribute' => 'researchTypeID',
                'value'=>function($model){
                  return $model->restypes->restypename;
                }
            ], 
            [
                'attribute' => 'projectStartDate',
                'value'=>function($model){
                  return $model->projectStartDate;
                }
            ],
            [
                'attribute' => 'projectEndDate',
                'value'=>function($model){
                  return $model->projectEndDate;
                }
            ],
            [
                'attribute' => 'jobStatusID',
                'value'=>function($model){
                  return $model->resstatuss->statusname;
                }
            ],
            [
                'attribute' => 'researchArea',
                'value'=>function($model){
                  return $model->researchArea;
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
        ],
    ]) ?>

</div>
