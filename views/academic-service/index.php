<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\AcademicServiceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'บริการวิชาการ';
$this->params['breadcrumbs'][] = $this->title;

$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$pos = $me ? (int)($me->position ?? 0) : 0;
$canCreate = in_array($pos, [1, 4], true);

$fmtDate = function ($v) {
    if (empty($v)) return '-';
    $ts = strtotime($v);
    return $ts ? date('d/m/Y', $ts) : $v;
};
?>
<div class="academic-service-index">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h3 class="mb-0"><i class="fas fa-hands-helping me-2"></i><?= Html::encode($this->title) ?></h3>
      <div class="text-muted small">บันทึกและติดตามรายการบริการวิชาการ</div>
    </div>

    <div>
      <?php if ($canCreate): ?>
        <?= Html::a('<i class="fas fa-plus me-1"></i> เพิ่มรายการ', ['create'], [
          'class' => 'btn btn-success',
          'encode' => false
        ]) ?>
      <?php endif; ?>
    </div>
  </div>

  <?= $this->render('_search', ['model' => $searchModel]); ?>

  <div class="card shadow-sm">
    <div class="card-body">

      <?php Pjax::begin(['id' => 'pjax-academic-service', 'timeout' => 10000]); ?>

      <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null, // ใช้ _search แทน
        'tableOptions' => ['class' => 'table table-striped table-bordered align-middle'],
        'columns' => [
          [
            'class' => 'yii\grid\ActionColumn',
            'header' => 'จัดการ',
            'contentOptions' => ['style' => 'width:110px; white-space:nowrap;'],
            'template' => '{view}',
            'buttons' => [
              'view' => function ($url, $m) {
                return Html::a('<i class="fas fa-eye"></i>', ['view', 'service_id' => $m->service_id], [
                  'class' => 'btn btn-sm btn-outline-secondary',
                  'encode' => false,
                  'title' => 'ดูรายละเอียด',
                  'data-pjax' => '0', // กันบางเคสที่อยากเปิดหน้าใหม่แบบเต็ม
                ]);
              },
            ],
          ],
          [
            'attribute' => 'title',
            'label' => 'เรื่อง',
            'value' => function ($m) { return $m->title; },
          ],
          [
            'label' => 'ประเภท',
            'value' => function ($m) {
              return $m->serviceType->type_name ?? '-';
            },
            'contentOptions' => ['style' => 'width:220px;'],
          ],
          [
            'attribute' => 'hours',
            'label' => 'ชั่วโมง',
            'format' => ['decimal', 2],
            'contentOptions' => ['style' => 'width:110px; text-align:right;'],
          ],
          [
            'attribute' => 'service_date',
            'label' => 'วันที่',
            'value' => function ($m) use ($fmtDate) { return $fmtDate($m->service_date); },
            'contentOptions' => ['style' => 'width:120px; white-space:nowrap;'],
          ],
          [
            'label' => 'เจ้าของ',
            'value' => function ($m) { return $m->ownerFullname ?: ($m->username ?? '-'); },
            'contentOptions' => ['style' => 'width:200px;'],
          ],
        ],
      ]); ?>

      <?php Pjax::end(); ?>

    </div>
  </div>

</div>
