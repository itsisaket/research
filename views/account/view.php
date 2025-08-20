<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\bootstrap4\Modal;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Account */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Accounts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="account-view">

    <h1><?= Html::encode($this->title) ?></h1>
<div class="panel panel-default">
<div class="panel-body">
    <div class="row">
        <div class="col-sm-8">
        <p> 
            <?= Html::a('ย้อนกลับ', ['index'], ['class' => 'btn btn-info']) ?>
            <?= Html::a('<i class="glyphicon glyphicon-edit"></i> แก้ไขข้อมูล', ['update', 'id' => $model->uid], ['class' => 'btn btn-primary']) ?>

        </p>

         <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
                    [
                        'label' => 'ชื่อ - สกุล',
                        'value'=>$model->hasprefix->prefixname.' '.$model->uname.' '.$model->luname,  
                    ],
                    [
                        'label' => 'อีเมล์',
                        'value'=>$model->email,
                    ],
                    [
                        'label' => 'เบอร์ติดต่อ',
                        'value'=> $model->tel,
                    ],
                    [
                        'label' => 'สังกัด',
                        'value'=>$model->hasorg->org_name,
                    ],
                    [
                        'label' => 'สถานะ',
                        'value'=>$model->hasposition->positionname,
                    ],
                ],
            ]) ?>
        </div>

    </div>

</div>
</div>
</div>
<?php
     
     Modal::begin([
       'id'=>'modal',
       'size'=>'modal-lg',
     ]);
   
      echo "<div id='modalContent'></div>";
   
   Modal::end();
   
   
   
   //javascript code
   $this->registerJs("$(function() {
   $('#button').click(function(){
   $('#modal').modal('show')
   .find('#modalContent')
   .load($(this).attr('value'));
   });
 })");
?>