<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use app\models\Organize;
use app\models\Utilization_type;

/* @var $this yii\web\View */
/* @var $model app\models\UtilizationSearch */

$orgItems  = ArrayHelper::map(Organize::find()->orderBy(['org_name' => SORT_ASC])->all(), 'org_id', 'org_name');
$typeItems = ArrayHelper::map(Utilization_type::find()->orderBy(['utilization_type_name' => SORT_ASC])->all(), 'utilization_type', 'utilization_type_name');

$hasAdvanced = !empty($model->org_id) || !empty($model->utilization_type)
    || !empty($model->username) || !empty($model->project_name)
    || !empty($model->date_from) || !empty($model->date_to);

// Preset top 4 ลักษณะการใช้ประโยชน์
$presetTypes = array_slice($typeItems, 0, 4, true);
?>

<div class="smart-search utilization-search card shadow-sm mb-3">
    <div class="card-body">

        <!-- Preset chips -->
        <div class="ss-presets mb-3">
            <span class="ss-preset-label"><i class="fas fa-bolt me-1"></i> ลักษณะการใช้:</span>
            <?php foreach ($presetTypes as $tid => $tname):
                $isActive = ((int)$model->utilization_type === (int)$tid);
                $params = Yii::$app->request->queryParams;
                if ($isActive) {
                    unset($params['UtilizationSearch']['utilization_type']);
                } else {
                    $params['UtilizationSearch']['utilization_type'] = $tid;
                }
                unset($params['page']);
            ?>
                <a href="<?= Url::to(array_merge(['index'], $params)) ?>" class="<?= $isActive ? 'active' : '' ?>" data-pjax="1">
                    <?= Html::encode($tname) ?>
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
                        'placeholder' => 'พิมพ์ชื่อโครงการ / หน่วยงานที่ใช้ประโยชน์ / ชื่อผู้บันทึก...',
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
                            data-bs-toggle="collapse" data-bs-target="#ut-adv"
                            aria-expanded="<?= $hasAdvanced ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-1"></i> ตัวกรอง
                    </button>

                    <?= $this->render('@app/views/_shared/_sort_dropdown', [
                        'options' => [
                            ['value' => '-utilization_date', 'label' => 'วันที่ใหม่ล่าสุด', 'icon' => 'fa-arrow-down-1-9'],
                            ['value' => 'utilization_date',  'label' => 'วันที่เก่าสุด',    'icon' => 'fa-arrow-up-1-9'],
                            ['value' => '-utilization_id',   'label' => 'เพิ่มล่าสุด',     'icon' => 'fa-clock'],
                            ['value' => 'project_name',      'label' => 'ชื่อ ก-ฮ',        'icon' => 'fa-arrow-down-a-z'],
                        ],
                        'current' => Yii::$app->request->get('sort', '-utilization_date'),
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
        <div class="collapse <?= $hasAdvanced ? 'show' : '' ?> mt-3" id="ut-adv">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">ชื่อโครงการ</label>
                    <?= $form->field($model, 'project_name')->textInput([
                        'placeholder' => 'ค้นจากชื่อโครงการ...',
                        'class' => 'form-control ss-quick-input',
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">ลักษณะการใช้ประโยชน์</label>
                    <?= $form->field($model, 'utilization_type')->widget(Select2::class, [
                        'data' => $typeItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">หน่วยงาน</label>
                    <?= $form->field($model, 'org_id')->widget(Select2::class, [
                        'data' => $orgItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <!-- ===== ช่วงวันที่ดำเนินการ ===== -->
                <div class="col-12">
                    <?= $this->render('@app/views/_shared/_date_range_presets', [
                        'model'       => $model,
                        'searchClass' => 'UtilizationSearch',
                        'label'       => 'ช่วงวันที่ดำเนินการ:',
                    ]) ?>
                </div>
                <div class="col-12 col-md-9">
                    <?= $this->render('@app/views/_shared/_date_range_field', [
                        'form'  => $form,
                        'model' => $model,
                        'label' => 'ช่วงวันที่ดำเนินการ',
                        'hint'  => 'กรองรายการที่ดำเนินการในช่วงนี้',
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Chips -->
        <?php
        $chips = [];
        if (!empty($model->q))            $chips[] = ['label' => 'ค้น: '.$model->q, 'attr' => 'q'];
        if (!empty($model->project_name)) $chips[] = ['label' => 'โครงการ: '.$model->project_name, 'attr' => 'project_name'];
        if (!empty($model->utilization_type) && isset($typeItems[$model->utilization_type])) {
            $chips[] = ['label' => 'ลักษณะ: '.$typeItems[$model->utilization_type], 'attr' => 'utilization_type'];
        }
        if (!empty($model->org_id) && isset($orgItems[$model->org_id])) {
            $chips[] = ['label' => 'หน่วยงาน: '.$orgItems[$model->org_id], 'attr' => 'org_id'];
        }
        if (!empty($model->date_from) || !empty($model->date_to)) {
            $chips[] = ['label' => 'วันที่: ' . ($model->date_from ?: '...') . ' ถึง ' . ($model->date_to ?: '...'), 'attr' => '__daterange__'];
        }
        ?>
        <?php if (!empty($chips)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $c):
                    $params = Yii::$app->request->queryParams;
                    if ($c['attr'] === '__daterange__') {
                        if (isset($params['UtilizationSearch'])) {
                            unset($params['UtilizationSearch']['date_from'], $params['UtilizationSearch']['date_to']);
                        }
                    } elseif (isset($params['UtilizationSearch'][$c['attr']])) {
                        unset($params['UtilizationSearch'][$c['attr']]);
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

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-utilization']) ?>
