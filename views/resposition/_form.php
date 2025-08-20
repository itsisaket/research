<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Resposition */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="resposition-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'res_positionname')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
