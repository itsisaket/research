<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use kartik\widgets\Select2;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\DepDrop;
use kartik\widgets\FileInput;
use kartik\date\DatePicker;

use yii\helpers\Url;
use yii\bootstrap4\Alert;
use yii\bootstrap4\Modal;

use aryelds\sweetalert\SweetAlert;

use app\models\Province;
use app\models\Amphur;
use app\models\District;
/* @var $this yii\web\View */
/* @var $model app\models\Utilization */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="utilization-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-12">
            
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'project_name')->textInput() ?>
        </div>
    </div>  
    <div class="row ">
        <div class="col-sm-6">
            <?= $form->field($model, 'org_id')->dropDownList($model->orgid, ['prompt' => 'เลือกหน่วยงาน..']) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'uid')->dropDownList($model->userid, ['prompt' => 'เลือกนักวิจัย..']) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'utilization_type')->dropDownList($model->utilizationtype, ['prompt' => 'เลือกการใช้ประโยชน์..']) ?>
            
            <?php // Html::Button('Save', ['value' => Url::to(['account/regis']), 'class' => 'btn-success', 'id' => 'button']); ?>
        </div>    
    </div>          
    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'utilization_date')->widget(DatePicker::className(), [
                                    //'language'=>'th',
                                    'name' => 'utilization_date',
                                    'options' => ['placeholder' => 'เลือกวันที่...'],
                                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                    'pickerIcon' => '<i class="fas fa-calendar-alt text-primary"></i>',
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'dd-mm-yyyy',
                                        'todayHighlight' => true,
                                    ]
                                ])
                                ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'utilization_add')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'province')->dropdownList(ArrayHelper::map(Province::find()->where(['PROVINCE_ID'=>33])->all(), 'PROVINCE_ID', 'PROVINCE_NAME'), ['id' => 'ddl-province', 'prompt' => 'เลือกจังหวัด']); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'district')->widget(DepDrop::classname(), ['options' => ['id' => 'ddl-amphur'], 'data' => $amphur, 'pluginOptions' => ['depends' => ['ddl-province'], 'placeholder' => 'เลือกอำเภอ...', 'url' => Url::to(['/utilization/get-amphur'])]]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'sub_district')->widget(DepDrop::classname(), ['data' => $sub_district, 'pluginOptions' => ['depends' => ['ddl-province', 'ddl-amphur'], 'placeholder' => 'เลือกตำบล...', 'url' => Url::to(['/utilization/get-district'])]]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'utilization_detail')->textarea(['rows' => 3]) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'utilization_refer')->textarea(['rows' => 3]) ?>
        </div>
    </div>  
<hr>
            <div class="form-group ">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
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
