<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\ArticleSearch */
/* @var $pubItems array */

$hasAdvanced = !empty($model->publication_type) || !empty($model->researcher_name) || !empty($model->article_th);
?>

<div class="smart-search article-search card shadow-sm mb-3">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'action'  => ['index'],
            'method'  => 'get',
            'options' => ['data-pjax' => 1, 'class' => 'mb-0'],
            'fieldConfig' => ['template' => '{input}{error}'],
        ]); ?>

        <!-- Quick search -->
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md">
                <div class="position-relative">
                    <i class="fas fa-search ss-quick-icon"></i>
                    <?= $form->field($model, 'q')->textInput([
                        'placeholder' => 'พิมพ์ชื่อบทความ / วารสาร / ชื่อผู้แต่ง / รหัส...',
                        'class' => 'form-control ss-quick-input',
                        'autocomplete' => 'off',
                    ])->label(false) ?>
                    <button type="button" class="ss-quick-clear">&times;</button>
                </div>
            </div>

            <div class="col-12 col-md-auto">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button"
                            data-bs-toggle="collapse" data-bs-target="#ar-adv"
                            aria-expanded="<?= $hasAdvanced ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-1"></i> ตัวกรองเพิ่มเติม
                    </button>
                    <?= Html::a('<i class="fas fa-undo me-1"></i> รีเซ็ต', ['index'], [
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'encode' => false,
                        'data-pjax' => 0,
                    ]) ?>
                    <span class="ss-loading small">
                        <span class="spinner-border spinner-border-sm"></span> กำลังค้นหา...
                    </span>
                </div>
            </div>
        </div>

        <!-- Advanced -->
        <div class="collapse <?= $hasAdvanced ? 'show' : '' ?> mt-3" id="ar-adv">
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">ชื่อบทความ (ไทย)</label>
                    <?= $form->field($model, 'article_th')->textInput([
                        'placeholder' => 'ค้นจากชื่อบทความ...',
                        'class' => 'form-control ss-quick-input',
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">ประเภทฐาน</label>
                    <?= $form->field($model, 'publication_type')->widget(Select2::class, [
                        'data' => $pubItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">ชื่อนักวิจัย</label>
                    <?= $form->field($model, 'researcher_name')->textInput([
                        'placeholder' => 'ชื่อ/นามสกุล',
                        'class' => 'form-control ss-quick-input',
                    ])->label(false) ?>
                </div>
            </div>
        </div>

        <!-- Active chips -->
        <?php
        $chips = [];
        if (!empty($model->q)) $chips[] = ['label' => 'ค้น: '.$model->q, 'attr' => 'q'];
        if (!empty($model->article_th)) $chips[] = ['label' => 'ชื่อ: '.$model->article_th, 'attr' => 'article_th'];
        if (!empty($model->publication_type) && isset($pubItems[$model->publication_type])) {
            $chips[] = ['label' => 'ประเภท: '.$pubItems[$model->publication_type], 'attr' => 'publication_type'];
        }
        if (!empty($model->researcher_name)) $chips[] = ['label' => 'นักวิจัย: '.$model->researcher_name, 'attr' => 'researcher_name'];
        ?>
        <?php if (!empty($chips)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $c):
                    $params = Yii::$app->request->queryParams;
                    if (isset($params['ArticleSearch'][$c['attr']])) {
                        unset($params['ArticleSearch'][$c['attr']]);
                    }
                ?>
                    <span class="ss-chip">
                        <?= Html::encode($c['label']) ?>
                        <a href="<?= Url::to(array_merge(['index'], $params)) ?>" data-pjax="0">&times;</a>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-article']) ?>
