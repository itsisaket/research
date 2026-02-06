<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\AcademicServiceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'บริการวิชาการ';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="academic-service-index">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h3 class="mb-0"><i class="fas fa-hands-helping me-2"></i><?= Html::encode($this->title) ?></h3>
      <div class="text-muted small">บันทึกและติดตามรายการบริการวิชาการ</div>
    </div>
    <div>
      <?= Html::a('<i class="fas fa-plus me-1"></i> เพิ่มรายการ', ['create'], [
        'class' => 'btn btn-success',
        'encode' => false
      ]) ?>
    </div>
  </div>

  <?= $this->render('_search', ['model' => $searchModel]); ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php Pjax::begin(); ?>

      <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null, // ใช้ _search แทน
        'tableOptions' => ['class' => 'table table-striped table-bordered align-middle'],
        'columns' => [
          ['class' => 'yii\grid\SerialColumn'],

          [
            'attribute' => 'service_date',
            'label' => 'วันที่',
            'value' => function($m){ return $m->service_date; },
            'contentOptions' => ['style' => 'width:120px; white-space:nowrap;'],
          ],
          [
            'label' => 'ประเภท',
            'value' => function($m){
              return $m->serviceType->type_name ?? '-';
            },
            'contentOptions' => ['style' => 'width:220px;'],
          ],
          [
            'attribute' => 'title',
            'label' => 'เรื่อง',
            'value' => function($m){ return $m->title; },
          ],
          [
            'attribute' => 'hours',
            'label' => 'ชั่วโมง',
            'format' => ['decimal', 2],
            'contentOptions' => ['style' => 'width:110px; text-align:right;'],
          ],
          [
            'label' => 'เจ้าของ',
            'value' => function($m){ return $m->ownerFullname; },
            'contentOptions' => ['style' => 'width:180px;'],
          ],

          [
            'class' => 'yii\grid\ActionColumn',
            'header' => 'จัดการ',
            'contentOptions' => ['style' => 'width:160px; white-space:nowrap;'],
            'buttons' => [
              'view' => function($url,$m){
                return Html::a('<i class="fas fa-eye"></i>', ['view','service_id'=>$m->service_id], [
                  'class' => 'btn btn-sm btn-outline-secondary me-1',
                  'encode' => false,
                  'title' => 'ดู'
                ]);
              },
              'update' => function($url,$m){
                return Html::a('<i class="fas fa-edit"></i>', ['update','service_id'=>$m->service_id], [
                  'class' => 'btn btn-sm btn-outline-primary me-1',
                  'encode' => false,
                  'title' => 'แก้ไข'
                ]);
              },
              'delete' => function($url,$m){
                // แสดงปุ่มลบเฉพาะ owner
                $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
                $isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$m->username);
                if (!$isOwner) return '';
                return Html::a('<i class="fas fa-trash-alt"></i>', ['delete','service_id'=>$m->service_id], [
                  'class' => 'btn btn-sm btn-outline-danger',
                  'encode' => false,
                  'title' => 'ลบ',
                  'data' => ['confirm' => 'ยืนยันการลบรายการนี้หรือไม่?', 'method' => 'post']
                ]);
              },
            ],
            'template' => '{view}{update}{delete}',
          ],
        ],
      ]); ?>

      <?php Pjax::end(); ?>
    </div>
  </div>

</div>
