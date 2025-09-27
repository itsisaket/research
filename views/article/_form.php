<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Organize;
use yii\helpers\ArrayHelper;

use kartik\widgets\Select2;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\DepDrop;
use kartik\widgets\FileInput;
use kartik\date\DatePicker;
use yii\helpers\Url;
use yii\bootstrap4\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\Article */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="article-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-12">
            
        </div>
    </div>
    <div class="row ">
        <div class="col-sm-12">
            <?= $form->field($model, 'article_th')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'article_eng')->textInput(['maxlength' => true]) ?>
        </div>
    </div>    
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'org_id')->dropDownList($model->orgid, ['prompt' => 'เลือกหน่วยงาน..']) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'uid')->dropDownList($model->userid, ['prompt' => 'เลือกนักวิจัย..']) ?>
        </div>
    </div>    
    <div class="row">
        <div class="col-sm-8">
            <?= $form->field($model, 'journal')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'publication_type')->dropDownList($model->publication, ['prompt' => 'เลือกประเภท..']) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'article_publish')->widget(DatePicker::className(), [
                                    //'language'=>'th',
                                    'name' => 'article_publish',
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
    </div>
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'status_ec')->radioList($model->Ec); ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'branch')->dropDownList($model->Branch, ['prompt' => 'เลือกสาขา..']) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'refer')->textarea(['rows' => 3]) ?>
        </div>
    </div>
    <hr>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
