<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use kartik\depdrop\DepDrop;
use kartik\date\DatePicker;
// ถ้าในโปรเจ็กต์คุณลง kartik/select2 ด้วยค่อย uncomment
// use kartik\select2\Select2;

use yii\helpers\Url;

use app\models\Province;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */
/* @var $form yii\widgets\ActiveForm */
/* @var $amphur array|null */
/* @var $sub_district array|null */

// กันกรณี controller ยังไม่ได้ส่งอะไรมาจริง ๆ
$amphur      = $amphur ?? [];
$subDistrict = $sub_district ?? [];

?>
<div class="researchpro-form">

    <?php $form = ActiveForm::begin(); ?>

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

    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'org_id')->dropDownList($model->orgid ?? [], [
                'prompt' => 'เลือกหน่วยงาน..'
            ]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'uid')->dropDownList($model->userid ?? [], [
                'prompt' => 'เลือกนักวิจัย..'
            ]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'branch')->dropDownList($model->Branch ?? [], [
                'prompt' => 'เลือกสาขา..'
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'researchFundID')->dropDownList($model->ResFund ?? [], [
                'prompt' => 'เลือกแหล่งทุน..'
            ]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'researchTypeID')->dropDownList($model->Restype ?? [], [
                'prompt' => 'เลือกประเภทวิจัย..'
            ]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'projectYearsubmit')->dropDownList($model->years ?? [], [
                'prompt' => 'เลือกปีเสนอ..'
            ]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'budgets')->textInput() ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'fundingAgencyID')->dropDownList($model->ResAgency ?? [], [
                'prompt' => 'เลือกหน่วยงานผู้ให้ทุน..'
            ]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'projectStartDate')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'เลือกวันที่...'],
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'pickerIcon' => '<i class="fas fa-calendar-alt text-primary"></i>',
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'dd-mm-yyyy',
                    'todayHighlight' => true,
                ],
            ]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'projectEndDate')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'เลือกวันที่...'],
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'pickerIcon' => '<i class="fas fa-calendar-alt text-primary"></i>',
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'dd-mm-yyyy',
                    'todayHighlight' => true,
                ],
            ]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'jobStatusID')->dropDownList($model->resstatus ?? [], [
                'prompt' => 'เลือกสถานะโครงการ..'
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'researchArea')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?php
            // ในตัวอย่างคุณล็อกจังหวัด 33 (ศรีสะเกษ) ไว้เลย
            $provinceItems = ArrayHelper::map(
                Province::find()->where(['PROVINCE_ID' => 33])->all(),
                'PROVINCE_ID',
                'PROVINCE_NAME'
            );
            ?>
            <?= $form->field($model, 'province')->dropDownList($provinceItems, [
                'id' => 'ddl-province',
                'prompt' => 'เลือกจังหวัด',
            ]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'district')->widget(DepDrop::class, [
                'options' => ['id' => 'ddl-amphur'],
                'data' => $amphur,
                'pluginOptions' => [
                    'depends' => ['ddl-province'],
                    'placeholder' => 'เลือกอำเภอ...',
                    'url' => Url::to(['/researchpro/get-amphur']),
                ],
            ]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'sub_district')->widget(DepDrop::class, [
                'data' => $subDistrict,
                'pluginOptions' => [
                    'depends' => ['ddl-province', 'ddl-amphur'],
                    'placeholder' => 'เลือกตำบล...',
                    'url' => Url::to(['/researchpro/get-district']),
                ],
            ]) ?>
        </div>
    </div>

    <hr>

    <div class="form-group">
        <?= Html::submitButton('<i class="fa fa-save"></i> บันทึก', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
