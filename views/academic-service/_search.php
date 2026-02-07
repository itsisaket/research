<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use app\models\AcademicServiceType;

/* @var $this yii\web\View */
/* @var $model app\models\AcademicServiceSearch */
/* @var $form yii\widgets\ActiveForm */

$typeItems = AcademicServiceType::getItems(true);

$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$pos = $me ? (int)($me->position ?? 0) : 0;
$isAdmin = ($pos === 4);
$isResearcher = ($pos === 1);

// พยายามดึงรายการ user แบบจำกัดสิทธิ์ (ถ้า Search model มี method)
$userItems = [];
if (method_exists($model, 'getUserid')) {
    $userItems = (array)$model->getUserid();
}
?>

<div class="academic-service-search card shadow-sm mb-3">
  <div class="card-body">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => ['data-pjax' => 1], // ถ้า index ครอบด้วย Pjax จะลื่นขึ้น
    ]); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <h6 class="mb-0"><i class="fas fa-filter me-1"></i> ค้นหา</h6>
      <div class="d-flex gap-2">
        <?= Html::submitButton('<i class="fas fa-search me-1"></i> ค้นหา', ['class' => 'btn btn-primary btn-sm', 'encode' => false]) ?>
        <?= Html::a('<i class="fas fa-undo me-1"></i> รีเซ็ต', ['index'], ['class' => 'btn btn-outline-secondary btn-sm', 'encode' => false, 'data-pjax' => 0]) ?>
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
        <?php
          // admin/researcher ค้นหาตามเจ้าของได้
          // ถ้ามี list → ใช้ Select2, ถ้าไม่มี → fallback เป็น textInput
          if ($isAdmin || $isResearcher) {
              if (!empty($userItems)) {
                  echo $form->field($model, 'username')->widget(Select2::class, [
                      'data' => $userItems,
                      'options' => ['placeholder' => 'เลือกเจ้าของ...'],
                      'pluginOptions' => ['allowClear' => true],
                  ])->label('เจ้าของ');
              } else {
                  echo $form->field($model, 'username')->textInput(['placeholder' => 'username เจ้าของ...'])->label('เจ้าของ (username)');
              }
          } else {
              // ผู้ใช้ทั่วไป: ไม่จำเป็นต้องค้นหาคนอื่น (กันเห็นข้อมูลคนอื่น)
              // ถ้าคุณอยากให้ค้นหาเฉพาะตัวเอง ให้ซ่อนไว้เป็นค่า username ของตัวเองก็ได้
              echo $form->field($model, 'username')->hiddenInput(['value' => $me ? (string)$me->username : ''])->label(false);
          }
        ?>
      </div>
    </div>

    <?php ActiveForm::end(); ?>

  </div>
</div>
