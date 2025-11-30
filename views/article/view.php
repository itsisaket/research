<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Article */

$this->title = 'การตีพิมพ์เผยแพร่';

$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="article-view">

 
<p> 
        <?= Html::a('ย้อนกลับ', ['index'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-edit"></i> แก้ไขข้อมูล', ['update', 'article_id' => $model->article_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-trash"></i> ลบข้อมูล', ['delete', 'article_id' => $model->article_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'username',
                'value'=>function($model){
                  return $model->user->uname.' '.$model->user->luname;
                }
              ], 
            [
                'attribute' => 'article_th',
                'value'=>function($model){
                  return $model->article_th;
                }
            ],            
            [
                'attribute' => 'article_eng',
                'value'=>function($model){
                  return $model->article_eng;
                }
            ],
            [
                'attribute' => 'org_id',
                'value'=>function($model){
                  return $model->hasorg->org_name;
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
                'attribute' => 'journal',
                'value'=>function($model){
                  return $model->journal;
                }
            ],
            [
              'attribute' => 'status_ec',
              'value'=>function($model){
                return $model->haec->ec_name;
              }
            ],
            [
            'attribute' => 'branch',
            'value'=>function($model){
              return $model->habranch->branch_name;
            }
            ],
            [
                'attribute' => 'refer',
                'value'=>function($model){
                  return $model->refer;
                }
            ],
        ],
    ]) ?>
</div>
