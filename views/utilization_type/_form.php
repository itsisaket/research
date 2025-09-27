<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization_type */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="utilization-type-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'utilization_type_name')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
