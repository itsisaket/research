<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Restype */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="restype-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'restypeid')->textInput() ?>

    <?= $form->field($model, 'restypename')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
