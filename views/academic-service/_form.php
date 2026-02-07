<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

use kartik\select2\Select2;
use kartik\date\DatePicker;

use app\models\AcademicServiceType;
use app\models\Organize;

/* @var $this yii\web\View */
/* @var $model app\models\AcademicService */
/* @var $form yii\widgets\ActiveForm */

$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$position = $me ? (int)($me->position ?? 0) : 0;
$isAdmin  = ($position === 4);

$session = Yii::$app->session;
$ty = $session['ty'] ?? null; // org_id จาก session (ที่คุณใช้ใน getOrgid)

// default service_date วันนี้
if (empty($model->service_date)) {
    $model->service_date = date('Y-m-d');
}

/** ====== DEFAULTS: ให้ผ่าน required เสมอ ====== */
// username (required)
if (empty($model->username) && $me && !empty($me->username)) {
    $model->username = (string)$me->username;
}

// org_id (ไม่ required แต่คุณซ่อนไว้ ควร set ให้ด้วย)
if (empty($model->org_id)) {
    if ($ty) {
        $model->org_id = (int)$ty;
    } elseif ($me && !empty($me->org_id)) {
        $model->org_id = (int)$me->org_id;
    }
}

$typeItems = AcademicServiceType::getItems(true);

// รายการผู้ใช้ตามสิทธิ์ (ใช้ method ใน model ของคุณ)
$userItems = method_exists($model, 'getUserid') ? $model->getUserid() : [];

// รายการหน่วยงาน (ใช้ method ใน model ของคุณ)
$orgItems  = method_exists($model, 'getOrgid') ? $model->getOrgid() : [];

// ชื่อเจ้าของรายการ
$ownerText = !empty($model->ownerFullname) ? $model->ownerFullname : ($model->username ?? '-');

// ชื่อหน่วยงานสำหรับแสดง (อ่านอย่างเดียว)
$orgName = '-';
if (!empty($model->org_id)) {
    $orgName = $orgItems[$model->org_id] ?? $model->org_id;
}
?>

<div class="academic-service-form">
<?php $form = ActiveForm::begin(); ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h5 class="mb-1"><i class="fas fa-hands-helping me-1"></i> บันทึกบริการวิชาการ</h5>
        <div class="text-muted small"><i class="fas fa-info-circle me-1"></i> กรอกข้อมูลให้ครบถ้วนก่อนบันทึก</div>
      </div>
      <div class="text-muted small"><i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y') ?></div>
    </div>

    <!-- ผู้บันทึก/หน่วยงาน -->
    <h6 class="mb-2"><i class="fas fa-user-tie me-1"></i> ผู้บันทึกและหน่วยงาน</h6>
    <hr class="mt-2 mb-3">

    <div class="row g-3 align-items-end">

      <!-- username -->
      <div class="col-12 col-md-6">
        <?php if ($isAdmin): ?>
          <?= $form->field($model, 'username', [
            'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-user\"></i></span>\n{input}\n</div>\n{error}"
          ])->widget(Select2::class, [
            'data' => $userItems,
            'options' => ['placeholder' => 'เลือกผู้บันทึก...'],
            'pluginOptions' => ['allowClear' => true],
          ]) ?>
        <?php else: ?>
          <label class="form-label">ผู้บันทึก/เจ้าของเรื่อง</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($ownerText) ?>" readonly>
          </div>
          <?= $form->field($model, 'username')->hiddenInput()->label(false) ?>
        <?php endif; ?>
      </div>

      <!-- org_id -->
      <div class="col-12 col-md-6">
        <?php if ($isAdmin && !empty($orgItems)): ?>
          <?= $form->field($model, 'org_id', [
            'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-building\"></i></span>\n{input}\n</div>\n{error}"
          ])->widget(Select2::class, [
            'data' => $orgItems,
            'options' => ['placeholder' => 'เลือกหน่วยงาน...'],
            'pluginOptions' => ['allowClear' => true],
          ]) ?>
        <?php else: ?>
          <label class="form-label">หน่วยงาน</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-building"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode((string)$orgName) ?>" readonly>
          </div>
          <?= $form->field($model, 'org_id')->hiddenInput()->label(false) ?>
        <?php endif; ?>
      </div>

    </div>

    <!-- ข้อมูลการปฏิบัติงาน -->
    <h6 class="mb-2 mt-4"><i class="fas fa-file-alt me-1"></i> ข้อมูลการปฏิบัติงาน</h6>
    <hr class="mt-2 mb-3">

    <div class="row g-3 align-items-end">

      <div class="col-12 col-md-3">
        <?= $form->field($model, 'service_date', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-calendar-check\"></i></span>\n{input}\n</div>\n{error}"
        ])->widget(DatePicker::class, [
          'type' => DatePicker::TYPE_INPUT,
          'options' => ['placeholder' => 'เลือกวันที่...'],
          'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
            'todayHighlight' => true,
          ],
        ]) ?>
      </div>

      <div class="col-12 col-md-5">
        <?= $form->field($model, 'type_id', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-tags\"></i></span>\n{input}\n</div>\n{error}"
        ])->widget(Select2::class, [
          'data' => $typeItems,
          'options' => ['placeholder' => 'เลือกประเภทบริการวิชาการ...'],
          'pluginOptions' => ['allowClear' => true],
        ]) ?>
      </div>

      <div class="col-12 col-md-4">
        <?= $form->field($model, 'hours', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-clock\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['type' => 'number', 'step' => '0.25', 'min' => 0, 'placeholder' => 'เช่น 3.50']) ?>
      </div>

      <div class="col-12">
        <?= $form->field($model, 'title', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-clipboard\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'เรื่อง/โครงการ/กิจกรรมบริการวิชาการ']) ?>
      </div>

      <div class="col-12 col-md-6">
        <?= $form->field($model, 'location', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-map-marker-alt\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'สถานที่ดำเนินการ (ถ้ามี)']) ?>
      </div>

      <div class="col-12 col-md-6">
        <?= $form->field($model, 'reference_url', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-link\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'ลิงก์/เอกสารอ้างอิง (ถ้ามี)']) ?>
      </div>

      <div class="col-12">
        <?= $form->field($model, 'work_desc', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-pen\"></i></span>\n{input}\n</div>\n{error}"
        ])->textarea(['rows' => 3, 'placeholder' => 'ลักษณะงาน/บทบาท/รายละเอียดเพิ่มเติม']) ?>
      </div>

      <div class="col-12">
        <?= $form->field($model, 'note', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-sticky-note\"></i></span>\n{input}\n</div>\n{error}"
        ])->textInput(['maxlength' => true, 'placeholder' => 'หมายเหตุ (ถ้ามี)']) ?>
      </div>

      <?php if ($isAdmin): ?>
      <div class="col-12 col-md-4">
        <?= $form->field($model, 'status', [
          'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"fas fa-traffic-light\"></i></span>\n{input}\n</div>\n{error}"
        ])->dropDownList([
          1 => 'ใช้งาน/ปกติ',
          0 => 'ปิด/ยกเลิก',
        ], ['prompt' => 'เลือกสถานะ...']) ?>
      </div>
      <?php endif; ?>

    </div>

  </div>

  <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="text-muted small">
      <i class="fas fa-shield-alt me-1"></i> ตรวจสอบความถูกต้องก่อนบันทึก
    </div>
    <div class="d-flex gap-2">
      <?= Html::submitButton('<i class="fas fa-save me-1"></i> บันทึก', ['class' => 'btn btn-success', 'encode' => false]) ?>
      <?= Html::resetButton('<i class="fas fa-undo me-1"></i> ล้างค่า', ['class' => 'btn btn-outline-secondary', 'encode' => false]) ?>
    </div>
  </div>

</div>

<?php ActiveForm::end(); ?>
</div>
