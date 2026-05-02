<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use app\models\AcademicServiceType;

/* @var $this yii\web\View */
/* @var $model app\models\AcademicServiceSearch */

$typeItems = AcademicServiceType::getItems(true);

$me  = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$pos = $me ? (int)($me->position ?? 0) : 0;
$isAdmin = ($pos === 4);
$isResearcher = ($pos === 1);

$userItems = [];
if (method_exists($model, 'getUserid')) {
    $userItems = (array)$model->getUserid();
}

$hasAdvanced = !empty($model->type_id) || !empty($model->username) || !empty($model->title) || !empty($model->location)
    || !empty($model->date_from) || !empty($model->date_to);

// Preset top 4 ประเภทบริการ
$presetTypes = array_slice($typeItems, 0, 4, true);
?>

<div class="smart-search academic-service-search card shadow-sm mb-3">
    <div class="card-body">

        <!-- Preset chips -->
        <div class="ss-presets mb-3">
            <span class="ss-preset-label"><i class="fas fa-bolt me-1"></i> ประเภท:</span>
            <?php foreach ($presetTypes as $tid => $tname):
                $isActive = ((int)$model->type_id === (int)$tid);
                $params = Yii::$app->request->queryParams;
                if ($isActive) {
                    unset($params['AcademicServiceSearch']['type_id']);
                } else {
                    $params['AcademicServiceSearch']['type_id'] = $tid;
                }
                unset($params['page']);
            ?>
                <a href="<?= Url::to(array_merge(['index'], $params)) ?>" class="<?= $isActive ? 'active' : '' ?>" data-pjax="1">
                    <?= Html::encode($tname) ?>
                </a>
            <?php endforeach; ?>

            <?php if ($me):
                // ปุ่ม "ของฉัน"
                $isMineActive = !empty($model->username) && $model->username === (string)$me->username;
                $params = Yii::$app->request->queryParams;
                if ($isMineActive) {
                    unset($params['AcademicServiceSearch']['username']);
                } else {
                    $params['AcademicServiceSearch']['username'] = (string)$me->username;
                }
                unset($params['page']);
            ?>
                <a href="<?= Url::to(array_merge(['index'], $params)) ?>" class="<?= $isMineActive ? 'active' : '' ?>" data-pjax="1">
                    <i class="fas fa-user me-1"></i> ของฉัน
                </a>
            <?php endif; ?>
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
                        'placeholder' => 'พิมพ์เรื่อง / สถานที่ / ลักษณะงาน / ชื่อเจ้าของ...',
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
                            data-bs-toggle="collapse" data-bs-target="#as-adv"
                            aria-expanded="<?= $hasAdvanced ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-1"></i> ตัวกรอง
                    </button>

                    <?= $this->render('@app/views/_shared/_sort_dropdown', [
                        'options' => [
                            ['value' => '-service_date', 'label' => 'วันที่ใหม่ล่าสุด', 'icon' => 'fa-arrow-down-1-9'],
                            ['value' => 'service_date',  'label' => 'วันที่เก่าสุด',    'icon' => 'fa-arrow-up-1-9'],
                            ['value' => '-hours',        'label' => 'ชั่วโมงมาก',       'icon' => 'fa-clock'],
                            ['value' => 'hours',         'label' => 'ชั่วโมงน้อย',      'icon' => 'fa-clock'],
                            ['value' => 'title',         'label' => 'ชื่อ ก-ฮ',         'icon' => 'fa-arrow-down-a-z'],
                        ],
                        'current' => Yii::$app->request->get('sort', '-service_date'),
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
        <div class="collapse <?= $hasAdvanced ? 'show' : '' ?> mt-3" id="as-adv">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">ประเภท</label>
                    <?= $form->field($model, 'type_id')->widget(Select2::class, [
                        'data' => $typeItems,
                        'options' => ['placeholder' => '-- ทั้งหมด --'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">เรื่อง</label>
                    <?= $form->field($model, 'title')->textInput([
                        'placeholder' => 'ค้นจากเรื่อง...',
                        'class' => 'form-control ss-quick-input',
                    ])->label(false) ?>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">เจ้าของ</label>
                    <?php if ($isAdmin || $isResearcher): ?>
                        <?php if (!empty($userItems)): ?>
                            <?= $form->field($model, 'username')->widget(Select2::class, [
                                'data' => $userItems,
                                'options' => ['placeholder' => '-- ทั้งหมด --'],
                                'pluginOptions' => ['allowClear' => true],
                            ])->label(false) ?>
                        <?php else: ?>
                            <?= $form->field($model, 'username')->textInput([
                                'placeholder' => 'username เจ้าของ...',
                                'class' => 'form-control ss-quick-input',
                            ])->label(false) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $form->field($model, 'username')->hiddenInput([
                            'value' => $me ? (string)$me->username : '',
                        ])->label(false) ?>
                        <span class="text-muted small">เห็นเฉพาะรายการของท่าน</span>
                    <?php endif; ?>
                </div>

                <!-- ===== ช่วงวันที่ปฏิบัติงาน ===== -->
                <div class="col-12">
                    <?= $this->render('@app/views/_shared/_date_range_presets', [
                        'model'       => $model,
                        'searchClass' => 'AcademicServiceSearch',
                        'label'       => 'ช่วงวันที่ปฏิบัติงาน:',
                    ]) ?>
                </div>
                <div class="col-12 col-md-9">
                    <?= $this->render('@app/views/_shared/_date_range_field', [
                        'form'  => $form,
                        'model' => $model,
                        'label' => 'ช่วงวันที่ปฏิบัติงาน',
                        'hint'  => 'กรองรายการที่ปฏิบัติงานในช่วงนี้',
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Chips -->
        <?php
        $chips = [];
        if (!empty($model->q))     $chips[] = ['label' => 'ค้น: '.$model->q, 'attr' => 'q'];
        if (!empty($model->title)) $chips[] = ['label' => 'เรื่อง: '.$model->title, 'attr' => 'title'];
        if (!empty($model->type_id) && isset($typeItems[$model->type_id])) {
            $chips[] = ['label' => 'ประเภท: '.$typeItems[$model->type_id], 'attr' => 'type_id'];
        }
        if (!empty($model->username) && isset($userItems[$model->username])) {
            $chips[] = ['label' => 'เจ้าของ: '.$userItems[$model->username], 'attr' => 'username'];
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
                        if (isset($params['AcademicServiceSearch'])) {
                            unset($params['AcademicServiceSearch']['date_from'], $params['AcademicServiceSearch']['date_to']);
                        }
                    } elseif (isset($params['AcademicServiceSearch'][$c['attr']])) {
                        unset($params['AcademicServiceSearch'][$c['attr']]);
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

<?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-academic-service']) ?>
