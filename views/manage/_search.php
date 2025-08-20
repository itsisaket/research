<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ManageSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="manage-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'manageid') ?>

    <?= $form->field($model, 'pro_id') ?>

    <?= $form->field($model, 'manage') ?>

    <?= $form->field($model, 'area') ?>

    <?= $form->field($model, 'output') ?>

    <?php // echo $form->field($model, 'outcome') ?>

    <?php // echo $form->field($model, 'impact') ?>

    <?php // echo $form->field($model, 'dayup') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
