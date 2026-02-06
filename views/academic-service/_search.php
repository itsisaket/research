<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use app\models\AcademicServiceType;

/* @var $this yii\web\View */
/* @var $model app\models\AcademicServiceSearch */
/* @var $form yii\widgets\ActiveForm */

$typeItems = AcademicServiceType::getItems(true);
?>

<div class="academic-service-search card shadow-sm mb-3">
  <div class="card-body">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <h6 class="mb-0"><i class="fas fa-filter me-1"></i> ค้นหา</h6>
      <div class="d-flex gap-2">
        <?= Html::submitButton('<i class="fas fa-search me-1"></i> ค้นหา', ['class' => 'btn btn-primary btn-sm', 'encode' => false]) ?>
        <?= Html::a('<i class="fas fa-undo me-1"></i> รีเซ็ต', ['index'], ['class' => 'btn btn-outline-secondary btn-sm', 'encode' => false]) ?>
      </div>
    </div>

    <div class="row g-2">
      <div class="col-12 col-md-5">
        <?= $form->field($model, 'title')->textInput(['placeholder' => 'ค้นจากเรื่อง...'])->label('เรื่อง') ?>
      </div>

      <div class="col-12 col-md-4">
        <?= $form->field($model, 'type_id')->widget(Select2::class, [
          'data' => $typeItems,
          'options' => ['placeholder' => 'เลือกประเภท...'],
          'pluginOptions' => ['allowClear' => true],
        ])->label('ประเภท') ?>
      </div>

      <div class="col-12 col-md-3">
        <?= $form->field($model, 'username')->textInput(['placeholder' => 'username เจ้าของ...'])->label('เจ้าของ (username)') ?>
      </div>
    </div>

    <?php ActiveForm::end(); ?>

  </div>
</div>
