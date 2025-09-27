<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use app\models\Organization;
use yii\helpers\ArrayHelper;


use kartik\widgets\Select2;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\DepDrop;
use kartik\widgets\FileInput;
use yii\helpers\Url;
use yii\bootstrap4\Alert;

//use aryelds\sweetalert\SweetAlert;
use app\models\Resyear;
use app\models\Province;
use app\models\Amphur;
use app\models\District;

/* @var $this yii\web\View */
/* @var $model app\models\Project */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="project-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'pro_name')->textInput(['maxlength' => true]) ?>
            <?php // $form->field($model, 'pro_keyword')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-4">
        <?= $form->field($model, 'pro_capital')->dropDownList($model->capitalid,['prompt'=>'เลือกแหล่งทุน..']) ?> 
    <?php if(Yii::$app->user->identity->position==1 OR Yii::$app->user->identity->position==2){  ?>
        <?= $form->field($model, 'uid')->hiddenInput(['readonly' => true]) ?> 
        <?= Yii::$app->user->identity->uname.' '.Yii::$app->user->identity->uname; ?>
    <?php }else{ ?>
        <?= $form->field($model, 'uid')->dropDownList($model->Userid,['prompt'=>'เลือกผู้วิจัย..']) ?> 
    <?php } ?>   
        </div>
        <div class="col-sm-4">
        <?= $form->field($model, 'pro_type')->dropDownList($model->restypeid,['prompt'=>'เลือกประเภทโครงการ..']) ?> 
        <?= $form->field($model, 'pro_position')->dropDownList($model->positionid,['prompt'=>'เลือกตำแหน่งนักวิจัย..']) ?> 
        </div>         
        <div class="col-sm-2">
        <?= $form->field($model, 'pro_year')->dropdownList(ArrayHelper::map(Resyear::find()->all(), 'resyear', 'resyear'), ['prompt' => 'เลือกปีงบประมาณ..']) ?>
        <?= $form->field($model, 'pro_status')->dropDownList($model->resstatusid,['prompt'=>'เลือกสถานะโครงการ..']) ?>
        </div>
        <div class="col-sm-2">
        <?= $form->field($model, 'pro_budget')->textInput() ?>  
        </div>        
    </div>
    <div class="row">
        <div class="col-sm-6">
        <?= $form->field($model, 'pro_location')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
        <?= $form->field($model, 'province')->dropdownList(ArrayHelper::map(Province::find()->where(['PROVINCE_ID'=>33])->all(), 'PROVINCE_ID', 'PROVINCE_NAME'), ['id' => 'ddl-province', 'prompt' => 'เลือกจังหวัด']); ?>
        </div> 
        <div class="col-sm-2">
        <?= $form->field($model, 'district')->widget(DepDrop::classname(), ['options' => ['id' => 'ddl-amphur'], 'data' => $amphur, 'pluginOptions' => ['depends' => ['ddl-province'], 'placeholder' => 'เลือกอำเภอ...', 'url' => Url::to(['/project/get-amphur'])]]); ?>
        </div> 
        <div class="col-sm-2">
        <?= $form->field($model, 'sub_district')->widget(DepDrop::classname(), ['data' => $sub_district, 'pluginOptions' => ['depends' => ['ddl-province', 'ddl-amphur'], 'placeholder' => 'เลือกตำบล...', 'url' => Url::to(['/project/get-district'])]]); ?>
        </div>                                 
    </div>    
    <?= $form->field($model, 'dayup')->hiddenInput(['value' => date('Y-m-d H:m:s'), 'readonly' => true])->label(false) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
