<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap4\Modal;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ArticleSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $contribCount array */
$contribCount = $contribCount ?? [];

$this->title = 'การตีพิมพ์เผยแพร่';
$this->params['breadcrumbs'][] = $this->title;

/**
 * Format วันที่จาก string หลาย format (DD-MM-YYYY / YYYY-MM-DD / DD/MM/YYYY)
 * → 'd/m/พ.ศ.'
 */
$fmtPublishDate = function ($v) {
    if (empty($v)) return '-';
    $s = trim((string)$v);

    // ตัด time ออก ถ้ามี
    if (strpos($s, ' ') !== false) {
        $s = explode(' ', $s)[0];
    }

    // ลอง parse หลาย format
    $ts = false;
    foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt !== false) {
            $ts = $dt->getTimestamp();
            break;
        }
    }
    if ($ts === false) {
        $ts = strtotime($s); // fallback
    }
    if ($ts === false) {
        return Html::encode($s); // ไม่ parse ได้ → คืนค่าเดิม
    }
    return date('d/m/', $ts) . ((int)date('Y', $ts) + 543);
};
?>
<div class="article-index">



    <p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

  <?php Pjax::begin([
      'id' => 'pjax-article',
      'timeout' => 8000,
      'clientOptions' => ['scrollTo' => false],
  ]); ?>

  <?php echo $this->render('_search', [ 'model' => $searchModel, 'pubItems' => $pubItems,]);?>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="text-muted small">
          พบทั้งหมด <strong><?= number_format($dataProvider->getTotalCount()) ?></strong> รายการ
      </div>
      <?php if (!Yii::$app->user->isGuest && $dataProvider->getTotalCount() > 0): ?>
          <?= Html::a(
              '<i class="fas fa-file-excel me-1"></i> ส่งออก Excel ตามผลค้นหา',
              array_merge(['export'], Yii::$app->request->queryParams),
              [
                  'class'   => 'btn btn-success btn-sm',
                  'encode'  => false,
                  'data-pjax' => 0,
                  'target'  => '_blank',
                  'rel'     => 'noopener',
                  'title'   => 'ดาวน์โหลด Excel ตามตัวกรองและคำค้นปัจจุบัน',
              ]
          ) ?>
      <?php endif; ?>
  </div>

  <div class="ss-grid-wrap">
<?php if ($dataProvider->getTotalCount() === 0): ?>
    <?= $this->render('@app/views/_shared/_empty_state', [
        'icon'    => 'fa-newspaper',
        'title'   => 'ไม่พบบทความตามเงื่อนไข',
        'message' => 'ลองเปลี่ยนคำค้น หรือเลือกประเภทฐานอื่น หรือล้างตัวกรองทั้งหมด',
    ]) ?>
<?php else: ?>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
          'attribute' => 'การจัดการ',
          'format'=>'raw',
          'value'=>function($model){
            return  Html::a('<i class="fas fa-eye"></i>', ['view', 'article_id' => $model->article_id], ['class' => 'btn btn-sm btn-outline-secondary']);    
            
          }
        ],          
        [
            'attribute' => 'article_th',
            'value'=>function($model){
              return $model->article_th;
            }
          ],
          [
            'attribute' => 'publication_type',
            'value'=>function($model){
              return $model->publi->publication_name;
            }
          ],
          [
            'attribute' => 'article_publish',
            'label' => 'วันที่เผยแพร่',
            'value' => function($model) use ($fmtPublishDate) {
                return $fmtPublishDate($model->article_publish);
            },
            'contentOptions' => ['style' => 'white-space:nowrap;'],
          ],
          [
            'attribute' => 'org_id',
            'value'=>function($model){
              return $model->hasorg->org_name;
            }
          ], 
          [
            'attribute' => 'username',
            'label' => 'ผู้บันทึก',
            'format' => 'raw',
            'value' => function($model) {
                if ($model->user) {
                    $name = trim(($model->user->uname ?? '') . ' ' . ($model->user->luname ?? ''));
                    return Html::encode($name !== '' ? $name : $model->username);
                }
                return '-';
            },
          ],
          [
            'label' => 'ผู้เขียนร่วม',
            'format' => 'raw',
            'contentOptions' => ['style' => 'width:130px;text-align:center;white-space:nowrap;'],
            'value' => function($model) use ($contribCount) {
                $n = (int)($contribCount[(int)$model->article_id] ?? 0);
                if ($n === 0) {
                    return '<span class="text-muted small">—</span>';
                }
                return '<span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;font-weight:600;">'
                     . '<i class="fas fa-users me-1"></i>' . $n . ' คน</span>';
            },
          ],

            

    ],
]); ?>
<?php endif; ?>
  </div>

  <?php Pjax::end(); ?>

</div>
