<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\ArticleSearch */
/* @var $pubItems array */

$hasAdvanced = !empty($model->publication_type) || !empty($model->researcher_name) || !empty($model->article_th)
    || !empty($model->date_from) || !empty($model->date_to);

// Preset top 5 ประเภทฐาน
$presetPubs = array_slice($pubItems, 0, 5, true);
?>

<div class="smart-search article-search card shadow-sm mb-3">
    <div class="card-body">

        <!-- ===== Preset chips ===== -->
        <div class="ss-presets mb-3">
            <span class="ss-preset-label"><i class="fas fa-bolt me-1"></i> ประเภทฐาน:</span>
            <?php foreach ($presetPubs as $pid => $pname):
                $isActive = ((int)$model->publication_type === (int)$pid);
                $params = Yii::$app->request->queryParams;
                if ($isActive) {
                    unset($params['ArticleSearch']['publication_type']);
                } else {
                    $params['ArticleSearch']['publication_type'] = $pid;
                }
                unset($params['page']);
            ?>
                <a href="<?= Url::to(array_merge(['index'], $params)) ?>" class="<?= $isActive ? 'active' : '' ?>" data-pjax="1">
                    <?= Html::encode($pname) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php $form = ActiveForm::begin([
            'action'  => ['index'],
            'method'  => 'get',
            'options' => ['data-pjax' => 1, 'class' => 'mb-0'],
            'fieldConfig' => ['template' => '{input}{error}'],
        ]); ?>

        <!-- Quick search -->
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md">
                <div class="position-relative ss-ac-wrap">
                    <i class="fas fa-search ss-quick-icon"></i>
                    <?= $form->field($model, 'q')->textInput([
                        'placeholder' => 'พิมพ์ชื่อบทความ / วารสาร / ชื่อผู้แต่ง / รหัส...',
                        'class' => 'form-control form-control-lg ss-quick-input',
                        'autocomplete' => 'off',
                        'data-suggest-url' => Url::to(['suggest']),
                    ])->label(false) ?>
                    <button type="button" class="ss-quick-clear">&times;</button>
                </div>
            </div>

            <div class="col-12 col-md-auto">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button"
                            data-bs-toggle="collapse" data-bs-target="#ar-adv"
                            aria-expanded="<?= $hasAdvanced ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-1"></i> ตัวกรอง
                    </button>

                    <?= $this->render('@app/views/_shared/_sort_dropdown', [
                        'options' => [
                            ['value' => '-article_publish', 'label' => 'เผยแพร่ใหม่ล่าสุด', 'icon' => 'fa-arrow-down-1-9'],
                            ['value' => 'article_publish',  'label' => 'เผยแพร่เก่าสุด',    'icon' => 'fa-arrow-up-1-9'],
                            ['value' => '-article_id',     'label' => 'เพิ่มล่าสุด',       'icon' => 'fa-clock'],
                            ['value' => 'article_th',      'label' => 'ชื่อ ก-ฮ',          'icon' => 'fa-arrow-down-a-z'],
                        ],
                        'current' => Yii::$app->request->get('sort', '-article_publish'),
                    ]) ?>

                    <?= Html::a('<i class="fas fa-undo"></i>', ['index'], [
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'encode' => false,
                        'data-pjax' => 0,
                        'title' => 'รีเซ็ตทั้งหมด',
                    ]) ?>
                    <span class="ss-loading small">
                        <span class="spinner-border spinner-border-sm"></span>
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

                <!-- ===== ช่วงวันที่เผยแพร่ ===== -->
                <div class="col-12">
                    <?= $this->render('@app/views/_shared/_date_range_presets', [
                        'model'       => $model,
                        'searchClass' => 'ArticleSearch',
                        'label'       => 'ช่วงวันที่เผยแพร่:',
                    ]) ?>
                </div>
                <div class="col-12 col-md-9">
                    <?= $this->render('@app/views/_shared/_date_range_field', [
                        'form'  => $form,
                        'model' => $model,
                        'label' => 'ช่วงวันที่เผยแพร่',
                        'hint'  => 'หรือเลือกช่วงด้วยปฏิทิน — กรองบทความที่เผยแพร่ในช่วงนี้',
                    ]) ?>
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
        if (!empty($model->date_from) || !empty($model->date_to)) {
            $chips[] = ['label' => 'วันที่เผยแพร่: ' . ($model->date_from ?: '...') . ' ถึง ' . ($model->date_to ?: '...'), 'attr' => '__daterange__'];
        }
        ?>
        <?php if (!empty($chips)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $c):
                    $params = Yii::$app->request->queryParams;
                    if ($c['attr'] === '__daterange__') {
                        if (isset($params['ArticleSearch'])) {
                            unset($params['ArticleSearch']['date_from'], $params['ArticleSearch']['date_to']);
                        }
                    } elseif (isset($params['ArticleSearch'][$c['attr']])) {
                        unset($params['ArticleSearch'][$c['attr']]);
                    }
                ?>
                    <span class="ss-chip">
                        <?= Html::encode($c['label']) ?>
                        <a href="<?= Url::to(array_merge(['index'], $params)) ?>" data-pjax="1">&times;</a>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-article']) ?>
