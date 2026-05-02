<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;

use app\models\Organize;
use app\models\Position;
use app\models\Prefix;

/* @var $this yii\web\View */
/* @var $model app\models\AccountSearch */

$orgItems    = ArrayHelper::map(Organize::find()->orderBy(['org_name' => SORT_ASC])->all(), 'org_id', 'org_name');
$posItems    = ArrayHelper::map(Position::find()->all(), 'position', 'positionname');
$prefixItems = [];
try {
    $prefixItems = ArrayHelper::map(Prefix::find()->all(), 'prefix', 'prefix_name');
} catch (\Throwable $e) { /* ถ้าไม่มี Prefix model ไม่ต้องแสดง dropdown นี้ */ }

$hasAdvanced = !empty($model->org_id) || !empty($model->position) || !empty($model->prefix)
    || !empty($model->dept_code) || !empty($model->email) || !empty($model->tel);

// Preset top 3 ตำแหน่ง (ใช้บ่อย: นักวิจัย, ผู้ดูแล)
$presetPositions = array_slice($posItems, 0, 4, true);
?>

<div class="smart-search account-search card shadow-sm mb-3">
    <div class="card-body">

        <!-- Preset chips ตำแหน่ง -->
        <div class="ss-presets mb-3">
            <span class="ss-preset-label"><i class="fas fa-bolt me-1"></i> ตำแหน่ง:</span>
            <?php foreach ($presetPositions as $pid => $pname):
                $isActive = ((int)$model->position === (int)$pid);
                $params = Yii::$app->request->queryParams;
                if ($isActive) {
                    unset($params['AccountSearch']['position']);
                } else {
                    $params['AccountSearch']['position'] = $pid;
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
                        'placeholder' => 'พิมพ์ชื่อ-สกุล / รหัสบุคลากร / อีเมล / โทรศัพท์...',
                        'class' => 'form-control form-control-lg ss-quick-input',
                        'autocomplete' => 'off',
                        'data-suggest-url' => Url::to(['suggest']),
                    ])->label(false) ?>
                    <button type="button" class="ss-quick-clear" title="ล้างคำค้น">&times;</button>
                </div>
            </div>

            <div class="col-12 col-md-auto">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button"
                            data-bs-toggle="collapse" data-bs-target="#ac-adv"
                            aria-expanded="<?= $hasAdvanced ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-1"></i> ตัวกรอง
                    </button>

                    <?= $this->render('@app/views/_shared/_sort_dropdown', [
                        'options' => [
                            ['value' => 'uname',   'label' => 'ชื่อ ก-ฮ',          'icon' => 'fa-arrow-down-a-z'],
                            ['value' => '-uname',  'label' => 'ชื่อ ฮ-ก',          'icon' => 'fa-arrow-up-a-z'],
                            ['value' => 'org_id',  'label' => 'หน่วยงาน',          'icon' => 'fa-building'],
                            ['value' => '-dayup',  'label' => 'อัปเดตล่าสุด',     'icon' => 'fa-clock'],
                            ['value' => 'username','label' => 'รหัสบุคลากร น้อย→มาก','icon' => 'fa-id-card'],
                        ],
                        'current' => Yii::$app->request->get('sort', 'uname'),
                    ]) ?>

                    <?= Html::a('<i class="fas fa-undo"></i>', ['index'], [
                        'class'   => 'btn btn-outline-secondary btn-sm',
                        'encode'  => false,
                        'data-pjax' => 0,
                        'title'   => 'รีเซ็ตทั้งหมด',
                    ]) ?>
                    <span class="ss-loading small">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Advanced -->
        <div class="collapse <?= $hasAdvanced ? 'show' : '' ?> mt-3" id="ac-adv">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">หน่วยงาน</label>
                    <?= $form->field($model, 'org_id')->widget(Select2::class, [
                        'data' => $orgItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">ตำแหน่ง/บทบาท</label>
                    <?= $form->field($model, 'position')->widget(Select2::class, [
                        'data' => $posItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <?php if (!empty($prefixItems)): ?>
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">คำนำหน้า</label>
                    <?= $form->field($model, 'prefix')->widget(Select2::class, [
                        'data' => $prefixItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>
                <?php endif; ?>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">อีเมล</label>
                    <?= $form->field($model, 'email')->textInput([
                        'placeholder' => 'ค้นจากอีเมล...',
                        'class' => 'form-control ss-quick-input',
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">โทรศัพท์</label>
                    <?= $form->field($model, 'tel')->textInput([
                        'placeholder' => 'ค้นจากเบอร์...',
                        'class' => 'form-control ss-quick-input',
                    ])->label(false) ?>
                </div>
            </div>
        </div>

        <!-- Active filter chips -->
        <?php
        $chips = [];
        if (!empty($model->q))     $chips[] = ['label' => 'ค้น: '.$model->q, 'attr' => 'q'];
        if (!empty($model->org_id) && isset($orgItems[$model->org_id])) {
            $chips[] = ['label' => 'หน่วยงาน: '.$orgItems[$model->org_id], 'attr' => 'org_id'];
        }
        if (!empty($model->position) && isset($posItems[$model->position])) {
            $chips[] = ['label' => 'ตำแหน่ง: '.$posItems[$model->position], 'attr' => 'position'];
        }
        if (!empty($model->prefix) && isset($prefixItems[$model->prefix])) {
            $chips[] = ['label' => 'คำนำหน้า: '.$prefixItems[$model->prefix], 'attr' => 'prefix'];
        }
        if (!empty($model->email)) $chips[] = ['label' => 'อีเมล: '.$model->email, 'attr' => 'email'];
        if (!empty($model->tel))   $chips[] = ['label' => 'โทร: '.$model->tel, 'attr' => 'tel'];
        ?>
        <?php if (!empty($chips)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $c):
                    $params = Yii::$app->request->queryParams;
                    if (isset($params['AccountSearch'][$c['attr']])) {
                        unset($params['AccountSearch'][$c['attr']]);
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

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'grid-user-pjax']) ?>
