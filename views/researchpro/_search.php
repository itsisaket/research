<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;

use app\models\Account;
use app\models\ResGency;
use app\models\Restype;
use app\models\Resstatus;

/* @var $this yii\web\View */
/* @var $model app\models\ResearchproSearch */

$yearItems = [];
$yNow = (int)date('Y') + 543;
for ($y = $yNow; $y >= $yNow - 10; $y--) {
    $yearItems[$y] = $y;
}

$fundItems   = ArrayHelper::map(ResGency::find()->orderBy(['fundingAgencyName' => SORT_ASC])->all(), 'fundingAgencyID', 'fundingAgencyName');
$typeItems   = ArrayHelper::map(Restype::find()->orderBy(['restypename' => SORT_ASC])->all(), 'restypeid', 'restypename');
$statusItems = ArrayHelper::map(Resstatus::find()->orderBy(['statusname' => SORT_ASC])->all(), 'statusid', 'statusname');
$userItems   = ArrayHelper::map(
    Account::find()->orderBy(['uname' => SORT_ASC])->all(),
    'username',
    function ($m) { return trim($m->uname . ' ' . $m->luname); }
);

$hasAdvanced = !empty($model->projectYearsubmit) || !empty($model->fundingAgencyID)
    || !empty($model->researchTypeID) || !empty($model->jobStatusID)
    || !empty($model->username) || !empty($model->projectNameTH)
    || !empty($model->date_from) || !empty($model->date_to);

// ===== Preset chips: 4 ปีงบล่าสุด + สถานะ =====
$buildPresetUrl = function ($attrPath, $value, $extraReset = []) {
    $params = Yii::$app->request->queryParams;
    // ล้างปีและสถานะออกก่อน เพื่อกันชนกัน
    if (!empty($params['ResearchproSearch'])) {
        foreach (['projectYearsubmit', 'jobStatusID', 'fundingAgencyID', 'researchTypeID'] as $k) {
            if (in_array($k, $extraReset, true)) {
                unset($params['ResearchproSearch'][$k]);
            }
        }
    }
    $parts = explode('.', $attrPath);
    $ref = &$params;
    foreach ($parts as $p) {
        if (!isset($ref[$p]) || !is_array($ref[$p])) $ref[$p] = [];
        $ref = &$ref[$p];
    }
    $ref = $value;
    unset($params['page']);
    return Url::to(array_merge(['index'], $params));
};

// ตัวเลือกสถานะ active 2 อันดับแรก (น่าจะเป็น "กำลังดำเนินการ" และ "เสร็จสมบูรณ์")
$presetStatuses = array_slice($statusItems, 0, 3, true);
?>

<div class="smart-search researchpro-search card shadow-sm mb-3">
    <div class="card-body">

        <!-- ===== Preset chips ===== -->
        <div class="ss-presets mb-3">
            <span class="ss-preset-label"><i class="fas fa-bolt me-1"></i> ลัด:</span>

            <?php
            // ปี 4 อันดับ
            $presetYears = array_slice($yearItems, 0, 4);
            foreach ($presetYears as $y => $label):
                $isActive = ((int)$model->projectYearsubmit === (int)$y);
                $url = $isActive
                    ? Url::to(array_merge(['index'], (function () { $p = Yii::$app->request->queryParams; unset($p['ResearchproSearch']['projectYearsubmit']); unset($p['page']); return $p; })()))
                    : Url::to(array_merge(['index'], (function () use ($y) { $p = Yii::$app->request->queryParams; $p['ResearchproSearch']['projectYearsubmit'] = $y; unset($p['page']); return $p; })()));
            ?>
                <a href="<?= $url ?>" class="<?= $isActive ? 'active' : '' ?>" data-pjax="1">
                    ปี <?= $label ?>
                </a>
            <?php endforeach; ?>

            <?php foreach ($presetStatuses as $sid => $sname):
                $isActive = ((int)$model->jobStatusID === (int)$sid);
                $url = $isActive
                    ? Url::to(array_merge(['index'], (function () { $p = Yii::$app->request->queryParams; unset($p['ResearchproSearch']['jobStatusID']); unset($p['page']); return $p; })()))
                    : Url::to(array_merge(['index'], (function () use ($sid) { $p = Yii::$app->request->queryParams; $p['ResearchproSearch']['jobStatusID'] = $sid; unset($p['page']); return $p; })()));
            ?>
                <a href="<?= $url ?>" class="<?= $isActive ? 'active' : '' ?>" data-pjax="1">
                    <?= Html::encode($sname) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php $form = ActiveForm::begin([
            'action'  => ['index'],
            'method'  => 'get',
            'options' => ['data-pjax' => 1, 'class' => 'mb-0'],
            'fieldConfig' => ['template' => '{input}{error}'],
        ]); ?>

        <!-- ===== Quick search row ===== -->
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md">
                <div class="position-relative ss-ac-wrap">
                    <i class="fas fa-search ss-quick-icon"></i>
                    <?= $form->field($model, 'q')->textInput([
                        'placeholder' => 'พิมพ์ชื่อโครงการ / ชื่อหัวหน้าโครงการ / รหัส / ปี เพื่อค้นหา...',
                        'class' => 'form-control form-control-lg ss-quick-input',
                        'autocomplete' => 'off',
                        'data-suggest-url' => Url::to(['suggest']),
                    ])->label(false) ?>
                    <button type="button" class="ss-quick-clear" title="ล้างคำค้น">&times;</button>
                </div>
            </div>

            <div class="col-12 col-md-auto">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm position-relative" type="button"
                            data-bs-toggle="collapse" data-bs-target="#rp-adv"
                            aria-expanded="<?= $hasAdvanced ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-1"></i> ตัวกรอง
                    </button>

                    <?= $this->render('@app/views/_shared/_sort_dropdown', [
                        'options' => [
                            ['value' => '-projectYearsubmit', 'label' => 'ปีล่าสุด',     'icon' => 'fa-arrow-down-wide-short'],
                            ['value' => 'projectYearsubmit',  'label' => 'ปีเก่าสุด',    'icon' => 'fa-arrow-up-wide-short'],
                            ['value' => '-budgets',           'label' => 'งบประมาณมาก',  'icon' => 'fa-money-bill-trend-up'],
                            ['value' => 'budgets',            'label' => 'งบประมาณน้อย', 'icon' => 'fa-money-bill-1'],
                            ['value' => 'projectNameTH',      'label' => 'ชื่อ ก-ฮ',     'icon' => 'fa-arrow-down-a-z'],
                        ],
                        'current' => Yii::$app->request->get('sort', '-projectYearsubmit'),
                    ]) ?>

                    <?= Html::a('<i class="fas fa-undo"></i>', ['index'], [
                        'class'  => 'btn btn-outline-secondary btn-sm',
                        'encode' => false,
                        'data-pjax' => 0,
                        'title'  => 'รีเซ็ตทั้งหมด',
                    ]) ?>
                    <span class="ss-loading small">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- ===== Advanced filters ===== -->
        <div class="collapse <?= $hasAdvanced ? 'show' : '' ?> mt-3" id="rp-adv">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">หัวหน้าโครงการ</label>
                    <?= $form->field($model, 'username')->widget(Select2::class, [
                        'data' => $userItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">ปีงบประมาณ</label>
                    <?= $form->field($model, 'projectYearsubmit')->widget(Select2::class, [
                        'data' => $yearItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">แหล่งทุน</label>
                    <?= $form->field($model, 'fundingAgencyID')->widget(Select2::class, [
                        'data' => $fundItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">ประเภทการวิจัย</label>
                    <?= $form->field($model, 'researchTypeID')->widget(Select2::class, [
                        'data' => $typeItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">สถานะงาน</label>
                    <?= $form->field($model, 'jobStatusID')->widget(Select2::class, [
                        'data' => $statusItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <!-- ===== ช่วงวันที่เริ่มโครงการ ===== -->
                <div class="col-12">
                    <?= $this->render('@app/views/_shared/_date_range_presets', [
                        'model'       => $model,
                        'searchClass' => 'ResearchproSearch',
                        'label'       => 'ช่วงวันที่เริ่มโครงการ:',
                    ]) ?>
                </div>
                <div class="col-12 col-md-9">
                    <?= $this->render('@app/views/_shared/_date_range_field', [
                        'form'  => $form,
                        'model' => $model,
                        'label' => 'ช่วงวันที่เริ่มโครงการ',
                        'hint'  => 'หรือเลือกช่วงด้วยปฏิทิน — กรองโครงการที่เริ่มในช่วงนี้',
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- ===== Active filter chips ===== -->
        <?php
        $chips = [];
        if (!empty($model->q)) $chips[] = ['label' => 'ค้น: '.$model->q, 'attr' => 'q'];
        if (!empty($model->username) && isset($userItems[$model->username])) {
            $chips[] = ['label' => 'หัวหน้า: '.$userItems[$model->username], 'attr' => 'username'];
        }
        if (!empty($model->projectYearsubmit)) {
            $chips[] = ['label' => 'ปี '.$model->projectYearsubmit, 'attr' => 'projectYearsubmit'];
        }
        if (!empty($model->fundingAgencyID) && isset($fundItems[$model->fundingAgencyID])) {
            $chips[] = ['label' => 'ทุน: '.$fundItems[$model->fundingAgencyID], 'attr' => 'fundingAgencyID'];
        }
        if (!empty($model->researchTypeID) && isset($typeItems[$model->researchTypeID])) {
            $chips[] = ['label' => 'ประเภท: '.$typeItems[$model->researchTypeID], 'attr' => 'researchTypeID'];
        }
        if (!empty($model->jobStatusID) && isset($statusItems[$model->jobStatusID])) {
            $chips[] = ['label' => 'สถานะ: '.$statusItems[$model->jobStatusID], 'attr' => 'jobStatusID'];
        }
        // chip ช่วงวันที่ — ลบทั้งคู่พร้อมกัน
        if (!empty($model->date_from) || !empty($model->date_to)) {
            $rangeLabel = 'วันที่: ' . ($model->date_from ?: '...') . ' ถึง ' . ($model->date_to ?: '...');
            $chips[] = ['label' => $rangeLabel, 'attr' => '__daterange__'];
        }
        ?>
        <?php if (!empty($chips)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $c):
                    $params = Yii::$app->request->queryParams;
                    if ($c['attr'] === '__daterange__') {
                        // ลบ date_from + date_to พร้อมกัน
                        if (isset($params['ResearchproSearch'])) {
                            unset($params['ResearchproSearch']['date_from'], $params['ResearchproSearch']['date_to']);
                        }
                    } elseif (isset($params['ResearchproSearch'][$c['attr']])) {
                        unset($params['ResearchproSearch'][$c['attr']]);
                    }
                ?>
                    <span class="ss-chip">
                        <?= Html::encode($c['label']) ?>
                        <a href="<?= Url::to(array_merge(['index'], $params)) ?>" data-pjax="1" title="ลบตัวกรอง">&times;</a>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-researchpro']) ?>
