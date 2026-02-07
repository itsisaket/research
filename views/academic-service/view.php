<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

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

// ตาม controller: create/update/delete เฉพาะ position 1 หรือ 4
$canEdit = ($isAdmin || ($pos === 1 && $isOwner));
$canDelete = ($isAdmin || $isOwner); // delete: admin ได้ทุกเคส / owner ได้

$safe = function ($v, $fallback = '-') {
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

// type name (กัน null)
$typeName = $model->serviceType->type_name ?? null;

// org name จาก map (กันกรณี method ไม่มี)
$orgName = null;
if (method_exists($model, 'getOrgid')) {
    $orgItems = (array)$model->getOrgid();
    if (!empty($model->org_id) && isset($orgItems[$model->org_id])) {
        $orgName = $orgItems[$model->org_id];
    }
}

// status badge
$status = (int)($model->status ?? 1);
$statusText = ($status === 1) ? 'ใช้งาน/ปกติ' : 'ปิด/ยกเลิก';
$statusClass = ($status === 1) ? 'bg-success' : 'bg-secondary';

// reference url render
$ref = trim((string)($model->reference_url ?? ''));
$refHtml = '-';
if ($ref !== '') {
    $refHtml = Html::a(Html::encode($ref), $ref, [
        'target' => '_blank',
        'rel' => 'noopener',
        'class' => 'text-decoration-none',
    ]);
}
?>

<div class="academic-service-view">

  <div class="card shadow-sm mb-3">
    <div class="card-body">

      <!-- Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h5 class="mb-1"><i class="fas fa-hands-helping me-1"></i> <?= Html::encode($this->title) ?></h5>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            ทุกคนดูได้ • แก้ไข/ลบตามสิทธิ์
          </div>
        </div>
        <div class="text-muted small d-flex flex-wrap gap-2 align-items-center">
          <span class="badge bg-light text-dark border">
            <i class="fas fa-hashtag me-1"></i> <?= Html::encode($model->service_id) ?>
          </span>
          <span class="badge <?= $statusClass ?>">
            <i class="fas fa-traffic-light me-1"></i> <?= Html::encode($statusText) ?>
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex gap-2">
          <?= Html::a('<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ', ['index'], [
              'class' => 'btn btn-outline-secondary',
              'encode' => false
          ]) ?>

          <?php if ($canEdit): ?>
            <?= Html::a('<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล', ['update', 'service_id' => $model->service_id], [
                'class' => 'btn btn-primary',
                'encode' => false
            ]) ?>
          <?php endif; ?>
        </div>

        <div>
          <?php if ($canDelete): ?>
            <?= Html::a('<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล', ['delete', 'service_id' => $model->service_id], [
              'class' => 'btn btn-danger',
              'encode' => false,
              'data' => ['confirm' => 'ยืนยันการลบรายการนี้หรือไม่?', 'method' => 'post'],
            ]) ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Badges Summary -->
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-light text-dark border">
          <i class="fas fa-calendar-alt me-1"></i><?= Html::encode($fmtDate($model->service_date)) ?>
        </span>
        <span class="badge bg-light text-dark border">
          <i class="fas fa-tags me-1"></i><?= Html::encode($safe($typeName)) ?>
        </span>
        <span class="badge bg-light text-dark border">
          <i class="fas fa-clock me-1"></i><?= Html::encode(number_format((float)$model->hours, 2)) ?> ชม.
        </span>
        <span class="badge bg-light text-dark border">
          <i class="fas fa-user-tie me-1"></i><?= Html::encode($safe($model->ownerFullname)) ?>
        </span>
        <span class="badge bg-light text-dark border">
          <i class="fas fa-building me-1"></i><?= Html::encode($safe($orgName ?? $model->org_id)) ?>
        </span>
      </div>

      <!-- Detail -->
      <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-bordered table-striped mb-0'],
        'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
        'attributes' => [
          ['label' => 'วันที่ปฏิบัติงาน', 'value' => $fmtDate($model->service_date)],
          ['label' => 'ประเภทบริการวิชาการ', 'value' => $safe($typeName)],
          ['label' => 'เรื่อง', 'value' => $safe($model->title)],
          ['label' => 'สถานที่', 'value' => $safe($model->location)],
          ['label' => 'ลักษณะงาน', 'format' => 'ntext', 'value' => $safe($model->work_desc)],
          ['label' => 'จำนวนชั่วโมงทำงาน', 'value' => number_format((float)$model->hours, 2)],
          ['label' => 'ลิงก์/อ้างอิง', 'format' => 'raw', 'value' => $refHtml],
          ['label' => 'ไฟล์แนบ', 'value' => $safe($model->attachment_path)],
          ['label' => 'เจ้าของรายการ (username)', 'value' => $safe($model->username)],
          ['label' => 'หน่วยงาน', 'value' => $safe($orgName ?? $model->org_id)],
          ['label' => 'สถานะ', 'value' => $statusText],
          ['label' => 'หมายเหตุ', 'format' => 'ntext', 'value' => $safe($model->note)],
          ['label' => 'สร้างเมื่อ', 'value' => $fmtDt($model->created_at)],
          ['label' => 'แก้ไขเมื่อ', 'value' => $fmtDt($model->updated_at)],
        ],
      ]) ?>

    </div>
  </div>

</div>
