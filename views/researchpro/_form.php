<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use kartik\depdrop\DepDrop;
use kartik\date\DatePicker;

use yii\helpers\Url;
use app\models\Province;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */
/* @var $form yii\widgets\ActiveForm */
/* @var $amphur array|null */
/* @var $sub_district array|null */

$amphur      = $amphur ?? [];
$subDistrict = $sub_district ?? [];

/* =========================
 * DEFAULTS from login user
 * ========================= */
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;

// ✅ เตรียมรายการ dropdown ครั้งเดียว (ใช้ getter ของ model)
$orgItems  = $model->orgid ?? [];
$userItems = $model->userid ?? [];

// 1) หัวหน้าโครงการ (tb_researchpro.username) = Account.username ของคน login
if (empty($model->username) && $me && !empty($me->username)) {
    $model->username = (string)$me->username;
}

// 2) หน่วยงาน (org_id) = org_id ของ user ที่ login
if (empty($model->org_id) && $me && !empty($me->org_id)) {
    $model->org_id = (int)$me->org_id;
}

// 3) กันกรณี default org_id อยู่ แต่ไม่อยู่ในรายการ (เช่น list ถูก filter)
if (!empty($model->org_id) && !isset($orgItems[$model->org_id]) && $me) {
    $orgItems[$model->org_id] = $me->dept_name ?? ('หน่วยงาน #' . $model->org_id);
}

// 4) กันกรณี default username อยู่ แต่ไม่อยู่ในรายการ (เช่น list ถูก filter)
// (ไม่ใช้ email) ถ้าระบบ sync ชื่อไม่มา จะใช้ “รหัสบุคลากร: xxx”
if (!empty($model->username) && !isset($userItems[$model->username])) {
    $userItems[$model->username] = 'รหัสบุคลากร: ' . $model->username;
}

// 5) ปีเสนอ/วันที่ (ตามเดิม)
$today = date('d-m-Y');
$thaiYear = (int)date('Y') + 543;

if (empty($model->projectYearsubmit) && isset($model->years[$thaiYear])) {
    $model->projectYearsubmit = $thaiYear;
}
if (empty($model->projectStartDate)) $model->projectStartDate = $today;
if (empty($model->projectEndDate))   $model->projectEndDate   = $today;

?>

<div class="researchpro-form">

<?php $form = ActiveForm::begin(); ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">

    <!-- ===== Header ===== -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h5 class="mb-1">
          <i class="fas fa-book-open me-1"></i> ข้อมูลโครงการวิจัย
        </h5>
        <div class="text-muted small">
          <i class="fas fa-info-circle me-1"></i> กรอกข้อมูลให้ครบถ้วนก่อนบันทึก
        </div>
      </div>
      <div class="text-muted small">
        <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y') ?>
      </div>
    </div>

    <!-- ===== ชื่อโครงการ ===== -->
    <h4 class="mb-2"><i class="fas fa-file-alt me-1"></i> ชื่อโครงการ</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12">
        <?= $form->field($model, 'projectNameTH', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-language\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'ชื่อโครงการ (ภาษาไทย)']) ?>
      </div>

      <div class="col-12">
        <?= $form->field($model, 'projectNameEN', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-font\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'Project Title (English)']) ?>
      </div>
    </div>

    <!-- ===== หน่วยงาน / หัวหน้าโครงการ ===== -->
    <h4 class="mt-4 mb-2"><i class="fas fa-building me-1"></i> หน่วยงานและหัวหน้าโครงการ</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12 col-md-8">
        <?= $form->field($model, 'org_id', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-sitemap\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($orgItems, [
          'prompt' => 'เลือกหน่วยงาน..'
        ]) ?>
      </div>

      <div class="col-12 col-md-4">
        <?= $form->field($model, 'username', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-user-tie\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList($userItems, [
          'prompt' => 'เลือกหัวหน้าโครงการ..'
        ]) ?>
      </div>
    </div>

    <!-- ===== รายละเอียดโครงการ ===== -->
    <h4 class="mt-4 mb-2"><i class="fas fa-clipboard-list me-1"></i> รายละเอียดโครงการ</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12 col-md-3">
        <?= $form->field($model, 'researchTypeID')->dropDownList($model->Restype ?? [], ['prompt' => 'เลือกประเภทวิจัย..']) ?>
      </div>
      <div class="col-12 col-md-3">
        <?= $form->field($model, 'branch')->dropDownList($model->Branch ?? [], ['prompt' => 'เลือกสาขา..']) ?>
      </div>
      <div class="col-12 col-md-3">
        <?= $form->field($model, 'projectYearsubmit')->dropDownList($model->years ?? [], ['prompt' => 'เลือกปีเสนอ..']) ?>
      </div>
      <div class="col-12 col-md-3">
        <?= $form->field($model, 'budgets', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-money-bill-wave\"></i></span>\n{input}\n<span class=\"input-group-text\">บาท</span>\n</div>\n{error}"
        ])->textInput(['inputmode'=>'numeric', 'placeholder'=>'เช่น 1500000']) ?>
      </div>
    </div>

    <!-- ===== ทุน/วันเริ่ม-สิ้นสุด/สถานะ ===== -->
    <h4 class="mt-4 mb-2"><i class="fas fa-coins me-1"></i> ทุนและระยะเวลา</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3">
      <div class="col-12 col-md-4">
        <?= $form->field($model, 'fundingAgencyID')->dropDownList($model->ResAgency ?? [], ['prompt' => 'เลือกหน่วยงานผู้ให้ทุน..']) ?>
      </div>
      <div class="col-12 col-md-4">
        <?= $form->field($model, 'researchFundID')->dropDownList($model->ResFund ?? [], ['prompt' => 'เลือกแหล่งทุน..']) ?>
      </div>
      <div class="col-12 col-md-4">
        <?= $form->field($model, 'jobStatusID')->dropDownList($model->resstatus ?? [], ['prompt' => 'เลือกสถานะโครงการ..']) ?>
      </div>

      <div class="col-12 col-md-4">
        <?= $form->field($model, 'projectStartDate')->widget(DatePicker::class, [
          'options' => ['placeholder' => 'เลือกวันที่เริ่ม...'],
          'type' => DatePicker::TYPE_INPUT,
          'pickerIcon' => '<i class="fas fa-calendar-plus text-primary"></i>',
          'pluginOptions' => [
            'autoclose' => true,
            'format' => 'dd-mm-yyyy',
            'todayHighlight' => true,
          ],
        ]) ?>
      </div>

      <div class="col-12 col-md-4">
        <?= $form->field($model, 'projectEndDate')->widget(DatePicker::class, [
          'options' => ['placeholder' => 'เลือกวันที่สิ้นสุด...'],
          'type' => DatePicker::TYPE_INPUT,
          'pickerIcon' => '<i class="fas fa-calendar-check text-success"></i>',
          'pluginOptions' => [
            'autoclose' => true,
            'format' => 'dd-mm-yyyy',
            'todayHighlight' => true,
          ],
        ]) ?>
      </div>
    </div>

    <!-- ===== พื้นที่วิจัย (คงของเดิม) ===== -->
    <h4 class="mt-4 mb-2"><i class="fas fa-map-marker-alt me-1"></i> พื้นที่วิจัย</h4>
    <hr class="mt-2 mb-3">

    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6">
          <?= $form->field($model, 'researchArea', [
            'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-map\"></i></span>\n{input}\n</div>\n{error}"
          ])->textInput(['maxlength' => true, 'placeholder' => 'เช่น อ.ขุนหาญ จ.ศรีสะเกษ']) ?>
        </div>

        <div class="col-12 col-md-2">
          <?php
          $provinceItems = ArrayHelper::map(
              Province::find()
                  ->orderBy(['PROVINCE_NAME' => SORT_ASC])
                  ->all(),
              'PROVINCE_ID',
              'PROVINCE_NAME'
          );
          if (empty($model->province)) $model->province = 33;
          ?>
          <?= $form->field($model, 'province')->dropDownList($provinceItems, [
              'id' => 'ddl-province',
              'type' => DepDrop::TYPE_SELECT2,
              'prompt' => 'เลือกจังหวัด',
          ]) ?>
        </div>

        <div class="col-12 col-md-2">
          <?= $form->field($model, 'district')->widget(DepDrop::class, [
              'options' => ['id' => 'ddl-amphur'],
              'type' => DepDrop::TYPE_SELECT2,
              'data' => $amphur ?? [],
              'pluginOptions' => [
                  'depends' => ['ddl-province'],
                  'placeholder' => 'เลือกอำเภอ...',
                  'url' => Url::to(['/researchpro/get-amphur']),
                  'initialize' => true,
              ],
          ]) ?>
        </div>

        <div class="col-12 col-md-2">
          <?= $form->field($model, 'sub_district')->widget(DepDrop::class, [
              'options' => ['id' => 'ddl-tambon'],
              'type' => DepDrop::TYPE_SELECT2,
              'data' => $subDistrict ?? [],
              'pluginOptions' => [
                  'depends' => ['ddl-province', 'ddl-amphur'],
                  'placeholder' => 'เลือกตำบล...',
                  'url' => Url::to(['/researchpro/get-district']),
                  'initialize' => true,
              ],
          ]) ?>
        </div>

  </div>

  <div class="card-footer bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
    <div class="text-muted small">
      <i class="fas fa-shield-alt me-1"></i> ตรวจสอบความถูกต้องก่อนบันทึก
    </div>
    <div class="d-flex gap-2">
      <?= Html::a('<i class="fas fa-arrow-left"></i> ย้อนกลับ', ['index'], ['class' => 'btn btn-outline-secondary', 'encode'=>false]) ?>
      <?= Html::submitButton('<i class="fas fa-save"></i> บันทึก', ['class' => 'btn btn-success', 'encode'=>false]) ?>
    </div>
  </div>

</div>

<?php ActiveForm::end(); ?>
</div>
