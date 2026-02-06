<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */

$this->title = 'รายละเอียดการใช้ประโยชน์';
$this->params['breadcrumbs'][] = ['label' => 'การนำไปใช้ประโยชน์', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

// owner check (ไว้แสดงปุ่มลบ)
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

// helper กัน null
$safe = function ($v, $fallback='-') {
    return (isset($v) && $v !== '' && $v !== null) ? $v : $fallback;
};
?>
<div class="utilization-view">

  <div class="card shadow-sm mb-3">
    <div class="card-body">

      <!-- Header -->
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h5 class="mb-1">
            <i class="fas fa-chart-line me-1"></i> <?= Html::encode($this->title) ?>
          </h5>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i> ทุกคนดูได้ (ปุ่มลบแสดงเฉพาะเจ้าของเรื่อง)
          </div>
        </div>
        <div class="text-muted small">
          <i class="fas fa-hashtag me-1"></i> <?= Html::encode($model->utilization_id) ?>
        </div>
      </div>

      <!-- Action bar: ซ้าย Back+Edit | ขวา Delete(owner) -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
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

      <!-- Detail -->
      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-0'],
          'template' => '<tr><th style="width:260px">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'project_name',
                  'label' => 'ชื่อโครงการ/ผลงาน',
                  'value' => $safe($model->project_name),
              ],
              [
                  'attribute' => 'username',
                  'label' => 'นักวิจัย',
                  'value' => function($model) use ($safe) {
                      $u = $model->user ?? null;
                      $full = $u ? trim(($u->uname ?? '').' '.($u->luname ?? '')) : '';
                      return $safe($full !== '' ? $full : null, $safe($model->username ?? null));
                  },
              ],
              [
                  'attribute' => 'org_id',
                  'label' => 'หน่วยงาน',
                  'value' => function($model) use ($safe) {
                      return $safe($model->hasorg->org_name ?? null, $safe($model->org_id ?? null));
                  },
              ],
              [
                  'attribute' => 'utilization_type',
                  'label' => 'ประเภทการใช้ประโยชน์',
                  'value' => function($model) use ($safe) {
                      return $safe($model->utilization->utilization_type_name ?? null, $safe($model->utilization_type ?? null));
                  },
              ],
              [
                  'attribute' => 'utilization_add',
                  'label' => 'สถานที่/ที่อยู่',
                  'value' => $safe($model->utilization_add),
              ],
              [
                  'attribute' => 'sub_district',
                  'label' => 'ตำบล',
                  'value' => function($model) use ($safe) {
                      return $safe($model->dist->DISTRICT_NAME ?? null, $safe($model->sub_district ?? null));
                  },
              ],
              [
                  'attribute' => 'district',
                  'label' => 'อำเภอ',
                  'value' => function($model) use ($safe) {
                      return $safe($model->amph->AMPHUR_NAME ?? null, $safe($model->district ?? null));
                  },
              ],
              [
                  'attribute' => 'province',
                  'label' => 'จังหวัด',
                  'value' => function($model) use ($safe) {
                      return $safe($model->prov->PROVINCE_NAME ?? null, $safe($model->province ?? null));
                  },
              ],
              [
                  'attribute' => 'utilization_date',
                  'label' => 'วันที่ใช้ประโยชน์',
                  'value' => $safe($model->utilization_date),
              ],
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
  </div>

</div>
