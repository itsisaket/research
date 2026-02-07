<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\Publication;

/* @var $this yii\web\View */
/* @var $model app\models\ArticleSearch */
/* @var $form yii\widgets\ActiveForm */

$pubItems = ArrayHelper::map(
    Publication::find()->orderBy(['publication_name' => SORT_ASC])->all(),
    'publication_type',
    'publication_name'
);
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
                <?= $form->field($model, 'publication_type')
                    ->dropDownList($pubItems, ['prompt' => '-- ประเภทฐาน --'])
                    ->label('ประเภทฐาน') ?>
            </div>

            <div class="col-12 col-md-3">
                <?= $form->field($model, 'researcher_name')
                    ->textInput(['placeholder' => 'ชื่อ/นามสกุล นักวิจัย']) ?>
            </div>

        </div>

        <div class="mt-3">
            <?= Html::submitButton('🔍 ค้นหา', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('รีเซ็ต', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
