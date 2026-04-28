<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UtilizationSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'การนำไปใช้ประโยชน์';

?>
<div class="utilization-index">


    <p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>

        <?php if (!Yii::$app->user->isGuest): ?>
            <?= Html::a(
                '<i class="fas fa-file-excel"></i> ส่งออก Excel',
                array_merge(['export'], Yii::$app->request->queryParams),
                [
                    'class'   => 'btn btn-outline-success',
                    'data-pjax' => 0,
                    'target'  => '_blank',
                    'rel'     => 'noopener',
                ]
            ) ?>
        <?php endif; ?>
    </p>

  <?php Pjax::begin([
      'id' => 'pjax-utilization',
      'timeout' => 8000,
      'clientOptions' => ['scrollTo' => false],
  ]); ?>

  <?php echo $this->render('_search', [ 'model' => $searchModel]);?>

  <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="text-muted small">
          พบทั้งหมด <strong><?= number_format($dataProvider->getTotalCount()) ?></strong> รายการ
      </div>
  </div>

  <div class="ss-grid-wrap">
<?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
              'label' => 'การจัดการ',
              'format' => 'raw',
              'value' => function($model){
                  return Html::a(
                      '<i class="fas fa-eye"></i>',
                      ['view', 'utilization_id' => $model->utilization_id],
                      ['class' => 'btn btn-sm btn-outline-secondary', 'encode' => false]
                  );
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
              'value' => function($model){
                  return $model->user ? ($model->user->uname.' '.$model->user->luname) : '-';
              }
            ],
            [
              'attribute' => 'org_id',
              'value' => function($model){
                  return $model->hasorg ? $model->hasorg->org_name : '-';
              }
            ],
            [
              'attribute' => 'utilization_type',
              'value' => function($model){
                  return $model->utilization ? $model->utilization->utilization_type_name : '-';
              }
            ],            

        ],
    ]); ?>
  </div>

  <?php Pjax::end(); ?>
</div>
