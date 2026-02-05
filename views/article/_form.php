<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model app\models\Article */
/* @var $form yii\widgets\ActiveForm */

/* =========================
 * DEFAULTS from login user (หลักการเดียวกัน)
 * ========================= */
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;

// ✅ เตรียมรายการ dropdown ครั้งเดียว
$orgItems   = $model->orgid ?? [];
$userItems  = $model->userid ?? [];
$pubItems   = $model->publication ?? [];
$branchItems= $model->Branch ?? [];
$ecItems    = $model->Ec ?? [];

// 1) นักวิจัย (username) = Account.username ของคน login (ให้สอดคล้องกับระบบคุณที่ใช้ username เป็นรหัสบุคลากร)
if (empty($model->username) && $me && !empty($me->username)) {
    $model->username = (string)$me->username;
}

// 2) หน่วยงาน (org_id) = org_id ของ user ที่ login
if (empty($model->org_id) && $me && !empty($me->org_id)) {
    $model->org_id = (int)$me->org_id;
}

// 3) กันกรณี default org_id อยู่แต่ไม่อยู่ใน list (ปลอดภัย)
if (!empty($model->org_id) && !isset($orgItems[$model->org_id])) {
    $orgItems[$model->org_id] = 'หน่วยงาน #' . $model->org_id;
}

// 4) กันกรณี default username อยู่แต่ไม่อยู่ใน list
if (!empty($model->username) && !isset($userItems[$model->username])) {
    $userItems[$model->username] = 'รหัสบุคลากร: ' . $model->username;
}

// 5) วันที่เผยแพร่: default วันนี้
$today = date('d-m-Y');
if (empty($model->article_publish)) {
    $model->article_publish = $today;
}

?>

<div class="article-form">

<?php $form = ActiveForm::begin(); ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">

    <!-- ===== Header ===== -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h5 class="mb-1">
          <i class="fas fa-newspaper me-1"></i> ข้อมูลบทความวิชาการ
        </h5>
        <div class="text-muted small">
          <i class="fas fa-info-circle me-1"></i> กรอกข้อมูลให้ครบถ้วนก่อนบันทึก
        </div>
      </div>
      <div class="text-muted small">
        <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y') ?>
      </div>
    </div>

    <!-- ===== ชื่อบทความ ===== -->
    <h6 class="mb-2"><i class="fas fa-file-alt me-1"></i> ชื่อบทความ</h6>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12">
        <?= $form->field($model, 'article_th', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-language\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'ชื่อบทความ (ภาษาไทย)']) ?>
      </div>

      <div class="col-12">
        <?= $form->field($model, 'article_eng', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-font\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'Article title (English)']) ?>
      </div>
    </div>

    <!-- ===== หน่วยงาน / นักวิจัย ===== -->
    <h6 class="mt-4 mb-2"><i class="fas fa-building me-1"></i> หน่วยงานและนักวิจัย</h6>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <?= $form->field($model, 'org_id', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-sitemap\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($orgItems, ['prompt' => 'เลือกหน่วยงาน..']) ?>
      </div>

      <div class="col-12 col-md-6">
        <?= $form->field($model, 'username', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-user-tie\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($userItems, ['prompt' => 'เลือกนักวิจัย..']) ?>
      </div>
    </div>

    <!-- ===== ข้อมูลการเผยแพร่ ===== -->
    <h6 class="mt-4 mb-2"><i class="fas fa-book me-1"></i> ข้อมูลการเผยแพร่</h6>
    <hr class="mt-2 mb-3">

    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-8">
        <?= $form->field($model, 'journal', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-journal-whills\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'ชื่อวารสาร/แหล่งเผยแพร่']) ?>
      </div>

      <div class="col-12 col-md-2">
        <?= $form->field($model, 'publication_type', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-tags\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($pubItems, ['prompt' => 'เลือกประเภท..']) ?>
      </div>

      <div class="col-12 col-md-2">
        <?= $form->field($model, 'article_publish', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-calendar-check\"></i></span>\n{input}\n</div>\n{error}"
        ])->widget(DatePicker::class, [
          'name' => 'article_publish',
          'options' => ['placeholder' => 'เลือกวันที่...'],
          'type' => DatePicker::TYPE_COMPONENT_APPEND,
          'pickerIcon' => '<i class="fas fa-calendar-alt text-primary"></i>',
          'pluginOptions' => [
            'autoclose' => true,
            'format' => 'dd-mm-yyyy',
            'todayHighlight' => true,
          ],
        ]) ?>
      </div>
    </div>

    <!-- ===== สถานะ / สาขา / อ้างอิง ===== -->
    <h6 class="mt-4 mb-2"><i class="fas fa-clipboard-check me-1"></i> สถานะและข้อมูลเพิ่มเติม</h6>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12 col-md-3">
        <?= $form->field($model, 'status_ec')->radioList($ecItems); ?>
      </div>

      <div class="col-12 col-md-3">
        <?= $form->field($model, 'branch', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-layer-group\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($branchItems, ['prompt' => 'เลือกสาขา..']) ?>
      </div>

      <div class="col-12 col-md-6">
        <?= $form->field($model, 'refer', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-link\"></i></span>\n{input}\n</div>\n{error}"
        ])->textarea(['rows' => 3, 'placeholder' => 'อ้างอิง/รายละเอียดเพิ่มเติม (เช่น DOI/URL/หมายเหตุ)']) ?>
      </div>
    </div>

  </div>

  <div class="card-footer bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
    <div class="text-muted small">
      <i class="fas fa-shield-alt me-1"></i> ตรวจสอบความถูกต้องก่อนบันทึก
    </div>
    <div class="d-flex gap-2">
      <?= Html::submitButton('<i class="fas fa-save"></i> บันทึก', ['class' => 'btn btn-success', 'encode' => false]) ?>
      <?= Html::resetButton('<i class="fas fa-undo"></i> ล้างค่า', ['class' => 'btn btn-outline-secondary', 'encode' => false]) ?>
    </div>
  </div>

</div>

<?php ActiveForm::end(); ?>

</div>
