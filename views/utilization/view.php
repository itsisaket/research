<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */

$this->title = 'รายละเอียดโครงการวิจัย';
$this->params['breadcrumbs'][] = ['label' => 'โครงการวิจัย', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);


/** ===== owner check: แสดงปุ่มลบ เฉพาะเจ้าของเรื่อง (username) ===== */
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

// ===== helper =====
$safe = fn($v, $f='-') => ($v !== null && $v !== '') ? $v : $f;
$money = fn($v) => is_numeric($v) ? number_format($v).' บาท' : '-';

?>
<div class="researchpro-view">

<div class="card shadow-sm mb-3">
<div class="card-body">

<!-- ===== Header ===== -->
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h5 class="mb-1">
      <i class="fas fa-book-open me-1"></i> <?= Html::encode($this->title) ?>
    </h5>
    <div class="text-muted small">
      <i class="fas fa-info-circle me-1"></i> ทุกคนดูได้และแก้ไขข้อมูลได้ (ปุ่มลบ แสดงเฉพาะเจ้าของเรื่อง)
    </div>
  </div>
  <div class="text-muted small">
    <i class="fas fa-hashtag me-1"></i> <?= Html::encode($model->projectID) ?>
  </div>
</div>

<!-- ===== Action Bar ===== -->
<div class="d-flex justify-content-between align-items-center mb-3">

  <!-- ซ้าย: ย้อนกลับ + แก้ไข (ทุกคนเห็น) -->
  <div class="d-flex gap-2">
    <?= Html::a('<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ', ['index'], [
        'class' => 'btn btn-outline-secondary',
        'encode' => false,
    ]) ?>

    <?= Html::a('<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล', ['update', 'projectID' => $model->projectID], [
        'class' => 'btn btn-primary',
        'encode' => false,
    ]) ?>
  </div>

  <!-- ขวา: ลบ (เฉพาะเจ้าของเรื่อง) -->
  <div>
    <?php if ($isOwner): ?>
      <?= Html::a('<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล', ['delete', 'projectID' => $model->projectID], [
          'class' => 'btn btn-danger',
          'encode' => false,
          'data' => [
              'confirm' => 'ยืนยันการลบโครงการนี้หรือไม่?',
              'method' => 'post',
          ],
      ]) ?>
    <?php endif; ?>
  </div>

</div>

<!-- ===== ข้อมูลโครงการ ===== -->
<?= DetailView::widget([
  'model' => $model,
  'options' => ['class'=>'table table-bordered table-striped'],
  'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
  'attributes' => [
    [
      'label' => 'ชื่อโครงการ (ไทย)',
      'value' => $safe($model->projectNameTH),
    ],
    [
      'label' => 'ชื่อโครงการ (อังกฤษ)',
      'value' => $safe($model->projectNameEN),
    ],
    [
      'label' => 'หัวหน้าโครงการ',
      'value' => $safe(($model->user->uname ?? '').' '.($model->user->luname ?? ''), $model->username),
    ],
    [
      'label' => 'หน่วยงาน',
      'value' => $safe($model->hasorg->org_name ?? null),
    ],
    [
      'label' => 'ปีเสนอ',
      'value' => $safe($model->projectYearsubmit),
    ],
    [
      'label' => 'งบประมาณ',
      'value' => $money($model->budgets),
    ],
    [
      'label' => 'วันที่เริ่ม',
      'value' => $safe($model->projectStartDate),
    ],
    [
      'label' => 'วันที่สิ้นสุด',
      'value' => $safe($model->projectEndDate),
    ],
    [
      'label' => 'พื้นที่วิจัย',
      'value' => $safe($model->researchArea),
    ],
  ],
]) ?>

</div>

<div class="card-footer bg-transparent d-flex justify-content-between">
  <small class="text-muted">
    <i class="fas fa-shield-alt me-1"></i>
    ปุ่มลบแสดงเฉพาะเจ้าของโครงการ
  </small>
  <small class="text-muted">
    <i class="fas fa-clock me-1"></i> <?= date('d/m/Y H:i') ?>
  </small>
</div>

</div>
</div>
