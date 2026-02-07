<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ArticleSearch */
/* @var $form yii\widgets\ActiveForm */
/* @var $pubItems array */

$pubItems = $pubItems ?? [];
?>

<div class="article-search card shadow-sm mb-3">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
        ]); ?>

        <div class="row g-3">

            <div class="col-12 col-md-6">
                <?= $form->field($model, 'article_th')
                    ->textInput(['placeholder' => 'ชื่อบทความ (ไทย)']) ?>
            </div>

            <div class="col-12 col-md-3">
                <?= $form->field($model, 'publication_type')->dropDownList(
                    $pubItems,
                    ['prompt' => '-- ประเภทฐาน --']
                ) ?>
            </div>

            <div class="col-12 col-md-3">
                <?= $form->field($model, 'researcher_name')
                    ->textInput(['placeholder' => 'ชื่อ/นามสกุล นักวิจัย']) ?>
            </div>

        </div>

        <div class="mt-3">
            <?= Html::submitButton('🔍 ค้นหา', ['class' => 'btn btn-primary']) ?>
            <?= Html::resetButton('รีเซ็ต', ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
