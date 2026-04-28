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
    || !empty($model->username) || !empty($model->project_name);
?>

<div class="smart-search utilization-search card shadow-sm mb-3">
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
                        'placeholder' => 'พิมพ์ชื่อโครงการ / หน่วยงานที่ใช้ประโยชน์ / ชื่อผู้บันทึก...',
                        'class' => 'form-control ss-quick-input',
                        'autocomplete' => 'off',
                    ])->label(false) ?>
                    <button type="button" class="ss-quick-clear">&times;</button>
                </div>
            </div>

            <div class="col-12 col-md-auto">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button"
                            data-bs-toggle="collapse" data-bs-target="#ut-adv"
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
        ?>
        <?php if (!empty($chips)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $c):
                    $params = Yii::$app->request->queryParams;
                    if (isset($params['UtilizationSearch'][$c['attr']])) {
                        unset($params['UtilizationSearch'][$c['attr']]);
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

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-utilization']) ?>
