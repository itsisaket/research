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

    <?php  echo $this->render('_search', ['model' => $searchModel]); ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
          'attribute' => 'การจัดการ',
          'format'=>'raw',
          'value'=>function($model){
            return  Html::a('จัดการข้อมูล', ['view', 'article_id' => $model->article_id], ['class' => 'btn btn-secondary']);     
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

</div>
