<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\AcademicService */

$this->title = 'รายละเอียดบริการวิชาการ';
$this->params['breadcrumbs'][] = ['label' => 'บริการวิชาการ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$pos = $me ? (int)($me->position ?? 0) : 0;

$isAdmin = ($pos === 4);
$isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);


$safe = function ($v, $fallback='-') {
    return (isset($v) && $v !== '' && $v !== null) ? $v : $fallback;
};

$fmtDate = function ($v) use ($safe) {
    if (empty($v)) return '-';
    $ts = strtotime($v);
    return $ts ? date('d/m/Y', $ts) : $safe($v);
};

$fmtDt = function ($v) {
    if (empty($v)) return '-';
    $ts = strtotime($v);
    return $ts ? date('d/m/Y H:i', $ts) : $v;
};

// type name
$typeName = $model->serviceType->type_name ?? null;

// org name จาก map (ถ้ามี)
$orgName = null;
if (method_exists($model, 'getOrgid')) {
    $orgItems = (array)$model->getOrgid();
    if (!empty($model->org_id) && isset($orgItems[$model->org_id])) {
        $orgName = $orgItems[$model->org_id];
    }
}

// status
$status = (int)($model->status ?? 1);
$statusText  = ($status === 1) ? 'ใช้งาน/ปกติ' : 'ปิด/ยกเลิก';
$statusClass = ($status === 1) ? 'bg-success' : 'bg-secondary';

// reference url render
$ref = trim((string)($model->reference_url ?? ''));
$refView = ($ref !== '')
    ? Html::a(Html::encode($ref), $ref, ['target' => '_blank', 'rel' => 'noopener', 'class' => 'text-decoration-none'])
    : '-';

$ownerText = !empty($model->ownerFullname) ? $model->ownerFullname : ($model->username ?? '-');
?>

<div class="academic-service-view">

  <div class="card shadow-sm mb-3">
    <div class="card-body">

      <!-- Header (เหมือนฟอร์ม) -->
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h5 class="mb-1"><i class="fas fa-hands-helping me-1"></i> <?= Html::encode($this->title) ?></h5>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i> แสดงรายละเอียดรายการ • แก้ไข/ลบตามสิทธิ์
          </div>
        </div>

        <div class="text-muted small d-flex flex-wrap gap-2 align-items-center">
          <span class="badge bg-light text-dark border">
            <i class="fas fa-hashtag me-1"></i> <?= Html::encode($model->service_id) ?>
          </span>
          <span class="badge <?= $statusClass ?>">
            <i class="fas fa-traffic-light me-1"></i> <?= Html::encode($statusText) ?>
          </span>
          <span class="text-muted small">
            <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y') ?>
          </span>
        </div>
      </div>

      <!-- Actions (จัดเหมือนฟอร์ม) -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex gap-2">
          <?= Html::a('<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ', ['index'], [
              'class' => 'btn btn-outline-secondary',
              'encode' => false
          ]) ?>

          <?php if ($isOwner): ?>
            <?= Html::a('<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล', ['update', 'service_id' => $model->service_id], [
                'class' => 'btn btn-primary',
                'encode' => false
            ]) ?>
          <?php endif; ?>
        </div>

        <div class="d-flex gap-2">
          <?php if ($isOwner): ?>
            <?= Html::a('<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล', ['delete', 'service_id' => $model->service_id], [
              'class' => 'btn btn-danger',
              'encode' => false,
              'data' => ['confirm' => 'ยืนยันการลบรายการนี้หรือไม่?', 'method' => 'post'],
            ]) ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Section 1: ผู้บันทึกและหน่วยงาน (เหมือนฟอร์ม) -->
      <h6 class="mb-2"><i class="fas fa-user-tie me-1"></i> ผู้บันทึกและหน่วยงาน</h6>
      <hr class="mt-2 mb-3">

      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6">
          <label class="form-label">ผู้บันทึก/เจ้าของเรื่อง</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($safe($ownerText)) ?>" readonly>
          </div>

        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">หน่วยงาน</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-building"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($safe($orgName ?? $model->org_id)) ?>" readonly>
          </div>
        </div>
      </div>

      <!-- Section 2: ข้อมูลการปฏิบัติงาน (เหมือนฟอร์ม) -->
      <h6 class="mb-2 mt-4"><i class="fas fa-file-alt me-1"></i> ข้อมูลการปฏิบัติงาน</h6>
      <hr class="mt-2 mb-3">

      <div class="row g-3 align-items-end">

        <div class="col-12 col-md-3">
          <label class="form-label">วันที่ปฏิบัติงาน</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($fmtDate($model->service_date)) ?>" readonly>
          </div>
        </div>

        <div class="col-12 col-md-5">
          <label class="form-label">ประเภทบริการวิชาการ</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-tags"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($safe($typeName)) ?>" readonly>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">จำนวนชั่วโมงทำงาน</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-clock"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode(number_format((float)$model->hours, 2)) ?> ชม." readonly>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">เรื่อง</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-clipboard"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($safe($model->title)) ?>" readonly>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">สถานที่</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($safe($model->location)) ?>" readonly>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">ลิงก์/อ้างอิง</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-link"></i></span>
            <div class="form-control bg-white" style="overflow:auto;">
              <?= $refView ?>
            </div>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">ลักษณะงาน</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-pen"></i></span>
            <textarea class="form-control" rows="3" readonly><?= Html::encode($safe($model->work_desc, '')) ?></textarea>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">หมายเหตุ</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($safe($model->note)) ?>" readonly>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">สร้างเมื่อ</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-plus-circle"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($fmtDt($model->created_at)) ?>" readonly>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">แก้ไขเมื่อ</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-edit"></i></span>
            <input type="text" class="form-control" value="<?= Html::encode($fmtDt($model->updated_at)) ?>" readonly>
          </div>
        </div>

      </div>

    </div>

    <!-- Footer (เหมือนฟอร์ม) -->
    <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="text-muted small">
        <i class="fas fa-shield-alt me-1"></i> ข้อมูลนี้เป็นรายการอ้างอิงของระบบ
      </div>
      <div class="text-muted small">
        <i class="fas fa-hashtag me-1"></i> ID: <strong><?= Html::encode($model->service_id) ?></strong>
      </div>
    </div>

  </div>

</div>
