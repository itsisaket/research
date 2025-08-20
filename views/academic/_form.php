<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Academic */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="academic-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'academicname')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'academiccode')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
