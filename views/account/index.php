<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;

$this->title = '';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="account-index">
<p><h1><?= Html::encode($this->title) ?></h1></p>

  <div class="panel panel-default">
    <div class="panel-body">
        <?php yii\widgets\Pjax::begin(['id' => 'grid-user-pjax','timeout'=>5000]) ?>
        <!-- เรียก view _search.php -->
        <?php echo $this->render('_search', ['model' => $searchModel]); ?>
        <hr>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'ชื่อ - สกุล',
                    'value'=>function($model){
                      return $model->hasprefix->prefixname.' '.$model->uname.' '.$model->luname;
                    }
                ],
                [
                  'attribute' => 'อีเมล์',
                  'value'=>function($model){
                    return $model->email;
                  }
                ],
                [
                  'attribute' => 'เบอร์ติดต่อ',
                  'value'=>function($model){
                    return $model->tel;
                  }
                ],
                [
                    'attribute' => 'สังกัด',
                    'value'=>function($model){
                      return $model->hasorg->org_name;
                    }
                ],
                [
                  'attribute' => 'สถานะ',
                  'value'=>function($model){
                    return $model->hasposition->positionname;
                  }
              ],
              [
                  'format' => 'raw',
                  'value' => function($model){
                      $user = Yii::$app->user->identity;
                      if (
                          $user && ($user->position == 4 || strtolower($user->position) == 'admin' || $user->id == $model->uid)
                      ) {
                          return
                              Html::a(
                                  '<i class="fa fa-edit"></i> แก้ไข',
                                  ['update', 'id' => $model->uid],
                                  ['class' => 'btn btn-warning btn-sm']
                              )
                              . ' ' .
                              Html::a(
                                  '<i class="fa fa-trash"></i> ลบ',
                                  ['delete', 'id' => $model->uid],
                                  [
                                      'class' => 'btn btn-danger btn-sm',
                                      'data' => [
                                          'confirm' => 'Are you sure you want to delete this item?',
                                          'method' => 'post',
                                      ],
                                  ]
                              );
                      }
                      return null;
                  }
              ],

              
            ],
        ]); 

        ?>

        <?php yii\widgets\Pjax::end() ?>
        
    </div>
  </div>
</div>


