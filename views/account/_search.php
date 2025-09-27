<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap4\Modal;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\AccountSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="account-search">

<?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => ['data-pjax' => true ]
    ]); ?>
    <div class="input-group">
      <?= Html::activeTextInput($model, 'q',['class'=>'form-control','placeholder'=>'ค้นหาข้อมูล...']) ?>
      <span class="input-group-btn">
        <button class="btn btn-success" type="submit"><i class="glyphicon glyphicon-search"></i> ค้นหา</button> 
        <?= Html::button('<i class="glyphicon glyphicon-user"></i> '.Yii::t('app', 'Create User'), ['value' => Url::to(['create']), 'class' => 'showModalButton btn btn-info', 'id' =>'button']);?>
      
      </span>
    </div>
    <?php ActiveForm::end(); ?>


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
