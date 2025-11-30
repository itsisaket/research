<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ArticleSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="article-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?php // echo $form->field($model, 'article_id') ?>

    <?= $form->field($model, 'article_th') ?>

    <?php // echo $form->field($model, 'article_eng') ?>

    <?php // echo $form->field($model, 'username') ?>

    <?php // echo $form->field($model, 'org_id') ?>

    <?php // echo $form->field($model, 'publication_type') ?>

    <?php // echo $form->field($model, 'article_publish') ?>

    <?php // echo $form->field($model, 'journal') ?>

    <?php // echo $form->field($model, 'refer') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
