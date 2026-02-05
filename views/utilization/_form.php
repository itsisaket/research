<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use kartik\depdrop\DepDrop;
use kartik\date\DatePicker;

use yii\helpers\Url;
use app\models\Province;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */
/* @var $form yii\widgets\ActiveForm */
/* @var $amphur array|null */
/* @var $sub_district array|null */

$amphur       = $amphur ?? [];
$sub_district = $sub_district ?? [];

/* =========================
 * DEFAULTS from login user (หลักการเดียวกับ ResearchPro)
 * ========================= */
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;

// ✅ เตรียมรายการ dropdown ครั้งเดียว
$orgItems  = $model->orgid ?? [];
$userItems = $model->userid ?? [];
$typeItems = $model->utilizationtype ?? [];

// 1) นักวิจัย (tb_utilization.username) = Account.username ของคน login
if (empty($model->username) && $me && !empty($me->username)) {
    $model->username = (string)$me->username;
}

// 2) หน่วยงาน (org_id) = org_id ของ user ที่ login
if (empty($model->org_id) && $me && !empty($me->org_id)) {
    $model->org_id = (int)$me->org_id;
}

// 3) กันกรณี default org_id อยู่แต่ไม่อยู่ใน list (เช่น list ถูก filter)
if (!empty($model->org_id) && !isset($orgItems[$model->org_id]) && $me) {
    $orgItems[$model->org_id] = $me->dept_name ?? ('หน่วยงาน #' . $model->org_id);
}

// 4) กันกรณี default username อยู่แต่ไม่อยู่ใน list
if (!empty($model->username) && !isset($userItems[$model->username])) {
    $userItems[$model->username] = 'รหัสบุคลากร: ' . $model->username;
}

// 5) วันที่ใช้ประโยชน์: default วันนี้
$today = date('d-m-Y');
if (empty($model->utilization_date)) {
    $model->utilization_date = $today;
}

// 6) จังหวัด: ล็อกศรีสะเกษเป็นค่าเริ่มต้น
if (empty($model->province)) {
    $model->province = 33;
}

?>

<div class="utilization-form">

<?php $form = ActiveForm::begin(); ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">

    <!-- ===== Header ===== -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h5 class="mb-1">
          <i class="fas fa-chart-line me-1"></i> บันทึกการใช้ประโยชน์ผลงานวิจัย
        </h5>
        <div class="text-muted small">
          <i class="fas fa-info-circle me-1"></i> กรอกข้อมูลให้ครบถ้วนก่อนบันทึก
        </div>
      </div>
      <div class="text-muted small">
        <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y') ?>
      </div>
    </div>

    <!-- ===== ข้อมูลโครงการ ===== -->
    <h4 class="mb-2"><i class="fas fa-file-signature me-1"></i> ข้อมูลโครงการ</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12">
        <?= $form->field($model, 'project_name', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-clipboard\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['placeholder' => 'ชื่อโครงการ/ผลงานที่นำไปใช้ประโยชน์']) ?>
      </div>
    </div>

    <!-- ===== หน่วยงาน / นักวิจัย / ประเภท / วันที่ ===== -->
    <h4 class="mt-4 mb-2"><i class="fas fa-building me-1"></i> หน่วยงานและรายละเอียดการใช้ประโยชน์</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-4">
        <?= $form->field($model, 'org_id', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-sitemap\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($orgItems, ['prompt' => 'เลือกหน่วยงาน..']) ?>
      </div>

      <div class="col-12 col-md-2">
        <?= $form->field($model, 'username', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-user-tie\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($userItems, ['prompt' => 'เลือกนักวิจัย..']) ?>
      </div>

      <div class="col-12 col-md-3">
        <?= $form->field($model, 'utilization_type', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-tags\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($typeItems, ['prompt' => 'เลือกการใช้ประโยชน์..']) ?>
      </div>

      <div class="col-12 col-md-3">
        <?= $form->field($model, 'utilization_date', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-calendar-check\"></i></span>\n{input}\n</div>\n{error}"
        ])->widget(DatePicker::class, [
          'options' => ['placeholder' => 'เลือกวันที่...'],
          'type' => DatePicker::TYPE_INPUT,
          'pickerIcon' => '<i class="fas fa-calendar-alt text-primary"></i>',
          'pluginOptions' => [
            'autoclose' => true,
            'format' => 'dd-mm-yyyy',
            'todayHighlight' => true,
          ],
        ]) ?>
      </div>
    </div>

    <!-- ===== สถานที่ ===== -->
    <h4 class="mt-4 mb-2"><i class="fas fa-map-marker-alt me-1"></i> สถานที่/พื้นที่ใช้ประโยชน์</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-6">
        <?= $form->field($model, 'utilization_add', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-location-arrow\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'สถานที่/หน่วยงาน/ที่อยู่ (สั้น ๆ)']) ?>
      </div>

      <div class="col-12 col-md-2">
        <?php
        $provinceItems = ArrayHelper::map(
            Province::find()->where(['PROVINCE_ID' => 33])->all(),
            'PROVINCE_ID',
            'PROVINCE_NAME'
        );
        ?>
        <?= $form->field($model, 'province')->dropDownList($provinceItems, [
          'id' => 'ddl-province',
          'prompt' => 'เลือกจังหวัด',
        ]) ?>
      </div>

      <div class="col-12 col-md-2">
        <?= $form->field($model, 'district')->widget(DepDrop::class, [
          'options' => ['id' => 'ddl-amphur'],
          'data' => $amphur,
          'pluginOptions' => [
            'depends' => ['ddl-province'],
            'placeholder' => 'เลือกอำเภอ...',
            'url' => Url::to(['/utilization/get-amphur']),
          ],
        ]) ?>
      </div>

      <div class="col-12 col-md-2">
        <?= $form->field($model, 'sub_district')->widget(DepDrop::class, [
          'data' => $sub_district,
          'pluginOptions' => [
            'depends' => ['ddl-province', 'ddl-amphur'],
            'placeholder' => 'เลือกตำบล...',
            'url' => Url::to(['/utilization/get-district']),
          ],
        ]) ?>
      </div>
    </div>

    <!-- ===== รายละเอียด/อ้างอิง ===== -->
    <h4 class="mt-4 mb-2"><i class="fas fa-align-left me-1"></i> รายละเอียดและเอกสารอ้างอิง</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <?= $form->field($model, 'utilization_detail', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-pen\"></i></span>\n{input}\n</div>\n{error}"
        ])->textarea(['rows' => 3, 'placeholder' => 'อธิบายรูปแบบ/ผลลัพธ์การใช้ประโยชน์โดยสรุป']) ?>
      </div>

      <div class="col-12 col-md-6">
        <?= $form->field($model, 'utilization_refer', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-link\"></i></span>\n{input}\n</div>\n{error}"
        ])->textarea(['rows' => 3, 'placeholder' => 'เอกสาร/ลิงก์/หลักฐานอ้างอิง (ถ้ามี)']) ?>
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
