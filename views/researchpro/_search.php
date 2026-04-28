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
    || !empty($model->username) || !empty($model->projectNameTH);
?>

<div class="smart-search researchpro-search card shadow-sm mb-3">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'action'   => ['index'],
            'method'   => 'get',
            'options'  => ['data-pjax' => 1, 'class' => 'mb-0'],
            'fieldConfig' => ['template' => '{input}{error}'],
        ]); ?>

        <!-- ==================== Quick search row ==================== -->
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md">
                <div class="position-relative">
                    <i class="fas fa-search ss-quick-icon"></i>
                    <?= $form->field($model, 'q')->textInput([
                        'placeholder' => 'พิมพ์ชื่อโครงการ / ชื่อหัวหน้าโครงการ / รหัส / ปี เพื่อค้นหา...',
                        'class' => 'form-control ss-quick-input',
                        'autocomplete' => 'off',
                    ])->label(false) ?>
                    <button type="button" class="ss-quick-clear" title="ล้างคำค้น">&times;</button>
                </div>
            </div>

            <div class="col-12 col-md-auto">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button"
                            data-bs-toggle="collapse" data-bs-target="#rp-adv"
                            aria-expanded="<?= $hasAdvanced ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-1"></i> ตัวกรองเพิ่มเติม
                    </button>
                    <?= Html::a('<i class="fas fa-undo me-1"></i> รีเซ็ต', ['index'], [
                        'class'   => 'btn btn-outline-secondary btn-sm',
                        'encode'  => false,
                        'data-pjax' => 0,
                    ]) ?>
                    <span class="ss-loading small">
                        <span class="spinner-border spinner-border-sm"></span> กำลังค้นหา...
                    </span>
                </div>
            </div>
        </div>

        <!-- ==================== Advanced filters (collapse) ==================== -->
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
            </div>
        </div>

        <!-- ==================== Active filter chips ==================== -->
        <?php
        $chips = [];
        if (!empty($model->q)) {
            $chips[] = ['label' => 'ค้น: '.$model->q, 'attr' => 'q'];
        }
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
        ?>
        <?php if (!empty($chips)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $c):
                    $params = Yii::$app->request->queryParams;
                    if (isset($params['ResearchproSearch'][$c['attr']])) {
                        unset($params['ResearchproSearch'][$c['attr']]);
                    }
                ?>
                    <span class="ss-chip">
                        <?= Html::encode($c['label']) ?>
                        <a href="<?= Url::to(array_merge(['index'], $params)) ?>" data-pjax="0" title="ลบตัวกรอง">&times;</a>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-researchpro']) ?>
