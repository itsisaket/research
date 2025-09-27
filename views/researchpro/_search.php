<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ResearchproSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="researchpro-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?php // echo $form->field($model, 'projectID') ?>

    <?= $form->field($model, 'projectNameTH') ?>

    <?php // echo $form->field($model, 'projectNameEN') ?>

    <?php // $form->field($model, 'uid') ?>

    <?php // echo $form->field($model, 'org_id') ?>

    <?php // echo $form->field($model, 'projectYearsubmit') ?>

    <?php // echo $form->field($model, 'budgets') ?>

    <?php // echo $form->field($model, 'fundingAgencyID') ?>

    <?php // echo $form->field($model, 'researchFundID') ?>

    <?php // echo $form->field($model, 'researchTypeID') ?>

    <?php // echo $form->field($model, 'projectStartDate') ?>

    <?php // echo $form->field($model, 'projectEndDate') ?>

    <?php // echo $form->field($model, 'jobStatusID') ?>

    <?php // echo $form->field($model, 'researchArea') ?>

    <?php // echo $form->field($model, 'sub_district') ?>

    <?php // echo $form->field($model, 'district') ?>

    <?php // echo $form->field($model, 'province') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
