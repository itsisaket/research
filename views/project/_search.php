<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProjectSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="project-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?php // $form->field($model, 'pro_id') ?>

    <?= $form->field($model, 'pro_name') ?>

    <?php // $form->field($model, 'uid') ?>

    <?php // $form->field($model, 'pro_position') ?>

    <?php // $form->field($model, 'pro_capital') ?>

    <?php // echo $form->field($model, 'pro_type') ?>

    <?php //  echo $form->field($model, 'pro_year') ?>

    <?php // echo $form->field($model, 'pro_budget') ?>

    <?php // echo $form->field($model, 'pro_status') ?>

    <?php // echo $form->field($model, 'pro_keyword') ?>

    <?php // echo $form->field($model, 'pro_location') ?>

    <?php // echo $form->field($model, 'subdistrict') ?>

    <?php // echo $form->field($model, 'district') ?>

    <?php // echo $form->field($model, 'province') ?>

    <?php // echo $form->field($model, 'dayup') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
