<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use kartik\widgets\Select2;
use kartik\widgets\TypeaheadBasic;
use kartik\depdrop\DepDrop;
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
/* @var $model app\models\Researchpro */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="researchpro-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-12">
            
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'projectNameTH')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'projectNameEN')->textInput(['maxlength' => true]) ?>
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
            <?= $form->field($model, 'branch')->dropDownList($model->Branch, ['prompt' => 'เลือกสาขา..']) ?>
        </div>
    </div> 
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'researchFundID')->dropDownList($model->ResFund) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'researchTypeID')->dropDownList($model->Restype) ?>
        </div>  
        <div class="col-sm-3">
            <?= $form->field($model, 'projectYearsubmit')->dropDownList($model->years) ?>
        </div>  
        <div class="col-sm-3">
            <?= $form->field($model, 'budgets')->textInput() ?>
        </div>  
    </div>
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'fundingAgencyID')->dropDownList($model->ResAgency) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'projectStartDate')->widget(DatePicker::className(), [
                                    //'language'=>'th',
                                    'name' => 'projectStartDate',
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
        <div class="col-sm-3">
            <?= $form->field($model, 'projectEndDate')->widget(DatePicker::className(), [
                                    //'language'=>'th',
                                    'name' => 'projectEndDate',
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
        <div class="col-sm-3">
            <?= $form->field($model, 'jobStatusID')->dropDownList($model->resstatus) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'researchArea')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'province')->dropdownList(ArrayHelper::map(Province::find()->where(['PROVINCE_ID'=>33])->all(), 'PROVINCE_ID', 'PROVINCE_NAME'), ['id' => 'ddl-province', 'prompt' => 'เลือกจังหวัด']); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'district')->widget(DepDrop::classname(), ['options' => ['id' => 'ddl-amphur'], 'data' => $amphur, 'pluginOptions' => ['depends' => ['ddl-province'], 'placeholder' => 'เลือกอำเภอ...', 'url' => Url::to(['/researchpro/get-amphur'])]]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'sub_district')->widget(DepDrop::classname(), ['data' => $sub_district, 'pluginOptions' => ['depends' => ['ddl-province', 'ddl-amphur'], 'placeholder' => 'เลือกตำบล...', 'url' => Url::to(['/researchpro/get-district'])]]); ?>
        </div>                        
    </div>
<hr>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
