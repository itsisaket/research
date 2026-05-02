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

$this->title = 'การตีพิมพ์เผยแพร่';
$this->params['breadcrumbs'][] = $this->title;
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
            'value'=>function($model){
              return $model->article_publish;
            }
          ], 
          [
            'attribute' => 'org_id',
            'value'=>function($model){
              return $model->hasorg->org_name;
            }
          ], 
          [
            'attribute' => 'username',
            'value'=>function($model){
              return $model->user->uname.' '.$model->user->luname;
            }
          ],            

            

    ],
]); ?>
<?php endif; ?>
  </div>

  <?php Pjax::end(); ?>

</div>
