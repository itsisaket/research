<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */

$this->title = 'รายละเอียดโครงการวิจัย';
$this->params['breadcrumbs'][] = ['label' => 'โครงการวิจัย', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

/** ===== owner check: แสดงปุ่มแก้ไข/ลบ เฉพาะเจ้าของเรื่อง (username) ===== */
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

/** ===== helpers กัน null ===== */
$safe = function ($v, $fallback = '-') {
    return (isset($v) && $v !== '' && $v !== null) ? $v : $fallback;
};
$money = function ($v) use ($safe) {
    return is_numeric($v) ? number_format((float)$v) : $safe($v);
};
?>
<div class="researchpro-view">

  <div class="card shadow-sm mb-3">
    <div class="card-body">

      <!-- ===== Header ===== -->
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h5 class="mb-1">
            <i class="fas fa-book-open me-1"></i> <?= Html::encode($this->title) ?>
          </h5>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i> ทุกคนดูได้(ปุ่มแก้ไขข้อมูลและปุ่มลบ แสดงเฉพาะเจ้าของเรื่อง)
          </div>
        </div>
        <div class="text-muted small">
          <i class="fas fa-hashtag me-1"></i> รหัสโครงการ: <?= Html::encode($model->projectID) ?>
        </div>
      </div>

      <!-- ===== Actions: ซ้าย (ย้อนกลับ+แก้ไข) | ขวา (ลบ) ===== -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
          <?= Html::a('<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ', ['index'], [
              'class' => 'btn btn-outline-secondary',
              'encode' => false,
          ]) ?>

          <?php if ($isOwner): ?>
            <?= Html::a('<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล', ['update', 'projectID' => $model->projectID], [
                'class' => 'btn btn-primary',
                'encode' => false,
            ]) ?>
          <?php endif; ?>
        </div>

        <div>
          <?php if ($isOwner): ?>
            <?= Html::a('<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล', ['delete', 'projectID' => $model->projectID], [
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

      <!-- ===== Section: ชื่อโครงการ ===== -->
      <h5 class="mb-2"><i class="fas fa-file-alt me-1"></i> ชื่อโครงการ</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'projectNameTH',
                  'label' => 'ชื่อโครงการ (ไทย)',
                  'value' => $safe($model->projectNameTH),
              ],
              [
                  'attribute' => 'projectNameEN',
                  'label' => 'ชื่อโครงการ (อังกฤษ)',
                  'value' => $safe($model->projectNameEN),
              ],
          ],
      ]) ?>

      <!-- ===== Section: หน่วยงานและหัวหน้าโครงการ ===== -->
      <h5 class="mb-2"><i class="fas fa-building me-1"></i> หน่วยงานและหัวหน้าโครงการ</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'username',
                  'label' => 'หัวหน้าโครงการ',
                  'value' => function($model) use ($safe) {
                      $full = trim(($model->user->uname ?? '') . ' ' . ($model->user->luname ?? ''));
                      return $safe($full !== '' ? $full : null, $safe($model->username ?? null));
                  },
              ],
              [
                  'attribute' => 'org_id',
                  'label' => 'หน่วยงาน',
                  'value' => function($model) use ($safe) {
                      return $safe($model->hasorg->org_name ?? null);
                  },
              ],
          ],
      ]) ?>

    <?= $this->render('//_shared/_contributors', [
        'refType' => 'article',
        'refId' => (int)$model->article_id,
        'isOwner' => $isOwner,
        'wrapCard' => false,
    ]) ?>
    
      <!-- ===== Section: รายละเอียดโครงการ ===== -->
      <h5 class="mb-2"><i class="fas fa-clipboard-list me-1"></i> รายละเอียดโครงการ</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'researchTypeID',
                  'label' => 'ประเภทวิจัย',
                  'value' => function($model) use ($safe) {
                      return $safe($model->restypes->restypename ?? null);
                  },
              ],
              [
                  'attribute' => 'branch',
                  'label' => 'สาขา',
                  'value' => function($model) use ($safe) {
                      return $safe($model->Branch[$model->branch] ?? ($model->branch ?? null));
                  },
              ],
              [
                  'attribute' => 'projectYearsubmit',
                  'label' => 'ปีเสนอ',
                  'value' => $safe($model->projectYearsubmit),
              ],
              [
                  'attribute' => 'budgets',
                  'label' => 'งบประมาณ',
                  'value' => function($model) use ($money) {
                      return $money($model->budgets) . ' บาท';
                  },
              ],
          ],
      ]) ?>

      <!-- ===== Section: ทุนและระยะเวลา ===== -->
      <h5 class="mb-2"><i class="fas fa-coins me-1"></i> ทุนและระยะเวลา</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'fundingAgencyID',
                  'label' => 'หน่วยงานผู้ให้ทุน',
                  'value' => function($model) use ($safe) {
                      return $safe($model->agencys->fundingAgencyName ?? null);
                  },
              ],
              [
                  'attribute' => 'researchFundID',
                  'label' => 'แหล่งทุน',
                  'value' => function($model) use ($safe) {
                      return $safe($model->resFunds->researchFundName ?? null);
                  },
              ],
              [
                  'attribute' => 'jobStatusID',
                  'label' => 'สถานะโครงการ',
                  'value' => function($model) use ($safe) {
                      return $safe($model->resstatuss->statusname ?? null);
                  },
              ],
              [
                  'attribute' => 'projectStartDate',
                  'label' => 'วันที่เริ่ม',
                  'value' => $safe($model->projectStartDate),
              ],
              [
                  'attribute' => 'projectEndDate',
                  'label' => 'วันที่สิ้นสุด',
                  'value' => $safe($model->projectEndDate),
              ],
          ],
      ]) ?>

      <!-- ===== Section: พื้นที่วิจัย ===== -->
      <h5 class="mb-2"><i class="fas fa-map-marker-alt me-1"></i> พื้นที่วิจัย</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-0'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'researchArea',
                  'label' => 'พื้นที่วิจัย (ข้อความ)',
                  'value' => $safe($model->researchArea),
              ],
              [
                  'attribute' => 'sub_district',
                  'label' => 'ตำบล',
                  'value' => function($model) use ($safe) {
                      return $safe($model->dist->DISTRICT_NAME ?? null);
                  },
              ],
              [
                  'attribute' => 'district',
                  'label' => 'อำเภอ',
                  'value' => function($model) use ($safe) {
                      return $safe($model->amph->AMPHUR_NAME ?? null);
                  },
              ],
              [
                  'attribute' => 'province',
                  'label' => 'จังหวัด',
                  'value' => function($model) use ($safe) {
                      return $safe($model->prov->PROVINCE_NAME ?? null);
                  },
              ],
          ],
      ]) ?>

    </div>

    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        <i class="fas fa-shield-alt me-1"></i> แสดงปุ่มแก้ไข/ลบเฉพาะเจ้าของเรื่อง (username)
      </div>
      <div class="text-muted small">
        <i class="fas fa-clock me-1"></i> <?= date('d/m/Y H:i') ?>
      </div>
    </div>

  </div>

</div>
