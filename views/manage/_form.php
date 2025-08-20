<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Manage */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="manage-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'pro_id')->textInput() ?>

    <?= $form->field($model, 'manage')->textInput() ?>

    <?= $form->field($model, 'area')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'output')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'outcome')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'impact')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'dayup')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
