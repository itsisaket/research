<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\depdrop\DepDrop;
use kartik\date\DatePicker;
use yii\helpers\Url;

use app\models\Province;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */
/* @var $form yii\widgets\ActiveForm */
/* @var $amphur array */
/* @var $sub_district array */

$amphur = $amphur ?? [];
$sub_district = $sub_district ?? [];

?>

<div class="utilization-form">

    <?php $form = ActiveForm::begin(); ?>

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
        </div>
    </div>

    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'utilization_date')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'เลือกวันที่...'],
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'dd-mm-yyyy',
                    'todayHighlight' => true,
                ]
            ]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'utilization_add')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'province')->dropDownList(
                ArrayHelper::map(
                    Province::find()->where(['PROVINCE_ID' => 33])->all(), // ถ้าจะใช้ทุกจังหวัด ให้เอา where ออก
                    'PROVINCE_ID',
                    'PROVINCE_NAME'
                ),
                ['id' => 'ddl-province', 'prompt' => 'เลือกจังหวัด']
            ); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'district')->widget(DepDrop::classname(), [
                'options' => ['id' => 'ddl-amphur'],
                'data' => $amphur,
                'pluginOptions' => [
                    'depends' => ['ddl-province'],
                    'placeholder' => 'เลือกอำเภอ...',
                    'url' => Url::to(['/utilization/get-amphur'])
                ]
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'sub_district')->widget(DepDrop::classname(), [
                'data' => $sub_district,
                'pluginOptions' => [
                    'depends' => ['ddl-province', 'ddl-amphur'],
                    'placeholder' => 'เลือกตำบล...',
                    'url' => Url::to(['/utilization/get-district'])
                ]
            ]); ?>
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

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
