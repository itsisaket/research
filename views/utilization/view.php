<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */

$this->title = 'รายละเอียดการใช้ประโยชน์';
$this->params['breadcrumbs'][] = ['label' => 'การนำไปใช้ประโยชน์', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

// ===== owner check: แสดงปุ่มลบเฉพาะเจ้าของ =====
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

// ===== helpers (PHP 7.4 compatible) =====
$safe = function ($v, $fallback = '-') {
    return (isset($v) && $v !== '' && $v !== null) ? $v : $fallback;
};

$fullName = function ($user) use ($safe) {
    if (!$user) return '-';
    $name = trim(($user->prefix ?? '') . ($user->uname ?? '') . ' ' . ($user->luname ?? ''));
    return $safe($name);
};

?>
<div class="utilization-view">

  <div class="card shadow-sm mb-3">
    <div class="card-body">

      <!-- ===== Header ===== -->
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h5 class="mb-1">
            <i class="fas fa-chart-line me-1"></i> <?= Html::encode($this->title) ?>
          </h5>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i> รายละเอียดการนำผลงานไปใช้ประโยชน์ (ทุกคนดูได้)
          </div>
        </div>

        <div class="text-muted small">
          <span class="badge bg-light text-dark border">
            <i class="fas fa-hashtag me-1"></i> <?= Html::encode($model->utilization_id) ?>
          </span>
        </div>
      </div>

      <!-- ===== Actions (ซ้าย: back+edit | ขวา: delete owner) ===== -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2">
          <?= Html::a('<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ', ['index'], [
              'class' => 'btn btn-outline-secondary',
              'encode' => false,
          ]) ?>

          <?= Html::a('<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล', ['update', 'utilization_id' => $model->utilization_id], [
              'class' => 'btn btn-primary',
              'encode' => false,
          ]) ?>
        </div>

        <div>
          <?php if ($isOwner): ?>
            <?= Html::a('<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล', ['delete', 'utilization_id' => $model->utilization_id], [
                'class' => 'btn btn-danger',
                'encode' => false,
                'data' => [
                    'confirm' => 'ยืนยันการลบรายการนี้หรือไม่?',
                    'method' => 'post',
                ],
            ]) ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- ===== Summary chips ===== -->
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-light text-dark border">
          <i class="fas fa-user-tie me-1"></i>
          <?= Html::encode($safe($fullName($model->user ?? null), $safe($model->username ?? null))) ?>
        </span>

        <span class="badge bg-light text-dark border">
          <i class="fas fa-sitemap me-1"></i>
          <?= Html::encode($safe($model->hasorg->org_name ?? null, $safe($model->org_id ?? null))) ?>
        </span>

        <span class="badge bg-light text-dark border">
          <i class="fas fa-tags me-1"></i>
          <?= Html::encode($safe($model->utilization->utilization_type_name ?? null, $safe($model->utilization_type ?? null))) ?>
        </span>

        <span class="badge bg-light text-dark border">
          <i class="fas fa-calendar-alt me-1"></i>
          <?= Html::encode($safe($model->utilization_date)) ?>
        </span>
      </div>

      <!-- ===== Section 1: ข้อมูลโครงการ ===== -->
      <h5 class="mb-2"><i class="fas fa-file-signature me-1"></i> ข้อมูลโครงการ</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'project_name',
                  'label' => 'ชื่อโครงการ/ผลงาน',
                  'value' => $safe($model->project_name),
              ],
              [
                  'attribute' => 'utilization_type',
                  'label' => 'ประเภทการใช้ประโยชน์',
                  'value' => $safe($model->utilization->utilization_type_name ?? null, $safe($model->utilization_type ?? null)),
              ],
              [
                  'attribute' => 'utilization_date',
                  'label' => 'วันที่ใช้ประโยชน์',
                  'value' => $safe($model->utilization_date),
              ],
          ],
      ]) ?>

      <!-- ===== Section 2: หน่วยงาน/ผู้รับผิดชอบ ===== -->
      <h5 class="mb-2"><i class="fas fa-building me-1"></i> หน่วยงานและผู้รับผิดชอบ</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'username',
                  'label' => 'นักวิจัย',
                  'value' => function ($model) use ($safe, $fullName) {
                      $u = $model->user ?? null;
                      $name = $u ? $fullName($u) : null;
                      return $safe($name, $safe($model->username ?? null));
                  },
              ],
              [
                  'attribute' => 'org_id',
                  'label' => 'หน่วยงาน',
                  'value' => $safe($model->hasorg->org_name ?? null, $safe($model->org_id ?? null)),
              ],
          ],
      ]) ?>

      <!-- ===== Section 3: สถานที่/พื้นที่ ===== -->
      <h5 class="mb-2"><i class="fas fa-map-marker-alt me-1"></i> สถานที่/พื้นที่ใช้ประโยชน์</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'utilization_add',
                  'label' => 'สถานที่/ที่อยู่',
                  'value' => $safe($model->utilization_add),
              ],
              [
                  'attribute' => 'sub_district',
                  'label' => 'ตำบล',
                  'value' => $safe($model->dist->DISTRICT_NAME ?? null, $safe($model->sub_district ?? null)),
              ],
              [
                  'attribute' => 'district',
                  'label' => 'อำเภอ',
                  'value' => $safe($model->amph->AMPHUR_NAME ?? null, $safe($model->district ?? null)),
              ],
              [
                  'attribute' => 'province',
                  'label' => 'จังหวัด',
                  'value' => $safe($model->prov->PROVINCE_NAME ?? null, $safe($model->province ?? null)),
              ],
          ],
      ]) ?>

      <!-- ===== Section 4: รายละเอียด/หลักฐาน ===== -->
      <h5 class="mb-2"><i class="fas fa-align-left me-1"></i> รายละเอียดและเอกสารอ้างอิง</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-0'],
          'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'utilization_detail',
                  'label' => 'รายละเอียด',
                  'format' => 'ntext',
                  'value' => $safe($model->utilization_detail),
              ],
              [
                  'attribute' => 'utilization_refer',
                  'label' => 'อ้างอิง/หลักฐาน',
                  'format' => 'ntext',
                  'value' => $safe($model->utilization_refer),
              ],
          ],
      ]) ?>

    </div>

    <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="text-muted small">
        <i class="fas fa-shield-alt me-1"></i> แสดงปุ่มลบเฉพาะเจ้าของรายการ (username)
      </div>
      <div class="text-muted small">
        <i class="fas fa-clock me-1"></i> <?= date('d/m/Y H:i') ?>
      </div>
    </div>

  </div>

</div>
