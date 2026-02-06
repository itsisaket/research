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
$isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

$safe = function ($v, $fallback='-') {
    return (isset($v) && $v !== '' && $v !== null) ? $v : $fallback;
};
?>
<div class="academic-service-view">

  <div class="card shadow-sm mb-3">
    <div class="card-body">

      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h5 class="mb-1"><i class="fas fa-hands-helping me-1"></i> <?= Html::encode($this->title) ?></h5>
          <div class="text-muted small"><i class="fas fa-info-circle me-1"></i> ทุกคนดูได้ (ลบเฉพาะเจ้าของเรื่อง)</div>
        </div>
        <div class="text-muted small">
          <span class="badge bg-light text-dark border">
            <i class="fas fa-hashtag me-1"></i> <?= Html::encode($model->service_id) ?>
          </span>
        </div>
      </div>

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex gap-2">
          <?= Html::a('<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ', ['index'], ['class' => 'btn btn-outline-secondary', 'encode'=>false]) ?>
          <?= Html::a('<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล', ['update', 'service_id' => $model->service_id], ['class' => 'btn btn-primary', 'encode'=>false]) ?>
        </div>
        <div>
          <?php if ($isOwner): ?>
            <?= Html::a('<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล', ['delete', 'service_id' => $model->service_id], [
              'class' => 'btn btn-danger',
              'encode' => false,
              'data' => ['confirm' => 'ยืนยันการลบรายการนี้หรือไม่?', 'method' => 'post'],
            ]) ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-light text-dark border"><i class="fas fa-calendar-alt me-1"></i><?= Html::encode($safe($model->service_date)) ?></span>
        <span class="badge bg-light text-dark border"><i class="fas fa-tags me-1"></i><?= Html::encode($safe($model->serviceType->type_name ?? null)) ?></span>
        <span class="badge bg-light text-dark border"><i class="fas fa-clock me-1"></i><?= Html::encode(number_format((float)$model->hours, 2)) ?> ชม.</span>
        <span class="badge bg-light text-dark border"><i class="fas fa-user-tie me-1"></i><?= Html::encode($safe($model->ownerFullname)) ?></span>
      </div>

      <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-bordered table-striped mb-0'],
        'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
        'attributes' => [
          ['label' => 'วันที่ปฏิบัติงาน', 'value' => $safe($model->service_date)],
          ['label' => 'ประเภทบริการวิชาการ', 'value' => $safe($model->serviceType->type_name ?? null)],
          ['label' => 'เรื่อง', 'value' => $safe($model->title)],
          ['label' => 'สถานที่', 'value' => $safe($model->location)],
          ['label' => 'ลักษณะงาน', 'format' => 'ntext', 'value' => $safe($model->work_desc)],
          ['label' => 'จำนวนชั่วโมงทำงาน', 'value' => number_format((float)$model->hours, 2)],
          ['label' => 'ลิงก์/อ้างอิง', 'format' => 'ntext', 'value' => $safe($model->reference_url)],
          ['label' => 'เจ้าของรายการ (username)', 'value' => $safe($model->username)],
          ['label' => 'หน่วยงาน (org_id)', 'value' => $safe($model->org_id)],
        ],
      ]) ?>

    </div>
  </div>

</div>
