<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use kartik\depdrop\DepDrop;
use kartik\date\DatePicker;

use yii\helpers\Url;

use app\models\Province;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */
/* @var $form yii\widgets\ActiveForm */
/* @var $amphur array|null */
/* @var $sub_district array|null */

$amphur      = $amphur ?? [];
$subDistrict = $sub_district ?? [];

/** ====== Defaults (ไม่เพิ่ม CSS, ใช้ bootstrap เดิม) ====== */
$today = date('d-m-Y');

// ปีเสนอ: ใช้ปี พ.ศ. ปัจจุบันเป็นค่าเริ่มต้น (กัน null)
$thaiYearNow = (int)date('Y') + 543;
if (empty($model->projectYearsubmit) && isset($model->years[$thaiYearNow])) {
    $model->projectYearsubmit = $thaiYearNow;
}

// วันที่เริ่ม/สิ้นสุด: ถ้ายังว่าง ให้ default วันนี้
if (empty($model->projectStartDate)) $model->projectStartDate = $today;
if (empty($model->projectEndDate))   $model->projectEndDate   = $today;

// นักวิจัย: ถ้าระบบมี user login และฟิลด์ว่าง ให้ default เป็น user ปัจจุบัน (ถ้าตรงกับรายการ)
if (empty($model->username) && !Yii::$app->user->isGuest) {
    $uid = Yii::$app->user->id;
    if (isset($model->userid[$uid])) $model->username = $uid;
}

?>
<div class="researchpro-form">

<?php $form = ActiveForm::begin([
    'options' => ['class' => 'needs-validation'],
]); ?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-journal-text me-1"></i>
            ข้อมูลโครงการวิจัย
        </h4>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i>
            กรอกข้อมูลให้ครบถ้วนเพื่อใช้ในระบบ ResearchPro
        </div>
    </div>
    <div class="text-muted small">
        <i class="bi bi-calendar-event me-1"></i>
        อัปเดต: <?= Html::encode(date('d/m/Y')) ?>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">

        <!-- ===== 1) ชื่อโครงการ ===== -->
        <div class="d-flex align-items-center mb-2">
            <h6 class="mb-0">
                <i class="bi bi-card-text me-1"></i>
                ชื่อโครงการ
            </h6>
        </div>
        <hr class="mt-2 mb-3">

        <div class="row g-3">
            <div class="col-12">
                <?= $form->field($model, 'projectNameTH', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-translate\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->textInput(['maxlength' => true, 'placeholder' => 'ระบุชื่อโครงการ (ภาษาไทย)']) ?>
            </div>
            <div class="col-12">
                <?= $form->field($model, 'projectNameEN', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-type\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->textInput(['maxlength' => true, 'placeholder' => 'Project title (English)']) ?>
            </div>
        </div>

        <!-- ===== 2) หน่วยงาน/นักวิจัย ===== -->
        <div class="d-flex align-items-center mt-4 mb-2">
            <h6 class="mb-0">
                <i class="bi bi-building me-1"></i>
                หน่วยงานและผู้รับผิดชอบ
            </h6>
        </div>
        <hr class="mt-2 mb-3">

        <div class="row g-3">
            <div class="col-12 col-md-8">
                <?= $form->field($model, 'org_id', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-diagram-3\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->orgid ?? [], [
                    'prompt' => 'เลือกหน่วยงาน..'
                ]) ?>
            </div>
            <div class="col-12 col-md-4">
                <?= $form->field($model, 'username', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-person-badge\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->userid ?? [], [
                    'prompt' => 'เลือกนักวิจัย..'
                ]) ?>
            </div>
        </div>

        <!-- ===== 3) ประเภท/สาขา/ปี/งบ ===== -->
        <div class="d-flex align-items-center mt-4 mb-2">
            <h6 class="mb-0">
                <i class="bi bi-clipboard-data me-1"></i>
                รายละเอียดโครงการ
            </h6>
        </div>
        <hr class="mt-2 mb-3">

        <div class="row g-3">
            <div class="col-12 col-md-3">
                <?= $form->field($model, 'researchTypeID', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-tags\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->Restype ?? [], [
                    'prompt' => 'เลือกประเภทวิจัย..'
                ]) ?>
            </div>
            <div class="col-12 col-md-3">
                <?= $form->field($model, 'branch', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-diagram-2\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->Branch ?? [], [
                    'prompt' => 'เลือกสาขา..'
                ]) ?>
            </div>
            <div class="col-12 col-md-3">
                <?= $form->field($model, 'projectYearsubmit', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-calendar2-week\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->years ?? [], [
                    'prompt' => 'เลือกปีเสนอ..'
                ]) ?>
            </div>
            <div class="col-12 col-md-3">
                <?= $form->field($model, 'budgets', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-cash-coin\"></i></span>\n{input}\n<span class=\"input-group-text\">บาท</span>\n</div>\n{hint}\n{error}"
                ])->textInput(['inputmode' => 'numeric', 'placeholder' => 'เช่น 1500000']) ?>
            </div>
        </div>

        <!-- ===== 4) แหล่งทุน/วันที่/สถานะ ===== -->
        <div class="d-flex align-items-center mt-4 mb-2">
            <h6 class="mb-0">
                <i class="bi bi-piggy-bank me-1"></i>
                ทุนวิจัยและช่วงเวลาดำเนินงาน
            </h6>
        </div>
        <hr class="mt-2 mb-3">

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <?= $form->field($model, 'fundingAgencyID', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-bank\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->ResAgency ?? [], [
                    'prompt' => 'เลือกหน่วยงานผู้ให้ทุน..'
                ]) ?>
            </div>
            <div class="col-12 col-md-4">
                <?= $form->field($model, 'researchFundID', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-wallet2\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->ResFund ?? [], [
                    'prompt' => 'เลือกแหล่งทุน..'
                ]) ?>
            </div>

            <div class="col-12 col-md-4">
                <?= $form->field($model, 'jobStatusID', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-flag\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($model->resstatus ?? [], [
                    'prompt' => 'เลือกสถานะโครงการ..'
                ]) ?>
            </div>

            <div class="col-12 col-md-4">
                <?= $form->field($model, 'projectStartDate', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-calendar-plus\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'เลือกวันที่เริ่ม...'],
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'pickerIcon' => '<i class="bi bi-calendar3"></i>',
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'dd-mm-yyyy',
                        'todayHighlight' => true,
                    ],
                ]) ?>
            </div>

            <div class="col-12 col-md-4">
                <?= $form->field($model, 'projectEndDate', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-calendar-check\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'เลือกวันที่สิ้นสุด...'],
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'pickerIcon' => '<i class="bi bi-calendar3"></i>',
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'dd-mm-yyyy',
                        'todayHighlight' => true,
                    ],
                ]) ?>
            </div>
        </div>

        <!-- ===== 5) พื้นที่วิจัย ===== -->
        <div class="d-flex align-items-center mt-4 mb-2">
            <h6 class="mb-0">
                <i class="bi bi-geo-alt me-1"></i>
                พื้นที่วิจัย
            </h6>
        </div>
        <hr class="mt-2 mb-3">

        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
                <?= $form->field($model, 'researchArea', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-pin-map\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->textInput(['maxlength' => true, 'placeholder' => 'เช่น อ.ขุนหาญ จ.ศรีสะเกษ']) ?>
            </div>

            <div class="col-12 col-md-2">
                <?php
                $provinceItems = ArrayHelper::map(
                    Province::find()->where(['PROVINCE_ID' => 33])->all(),
                    'PROVINCE_ID',
                    'PROVINCE_NAME'
                );
                // ถ้าล็อกจังหวัดไว้ ให้ default เป็น 33 เมื่อว่าง
                if (empty($model->province)) $model->province = 33;
                ?>
                <?= $form->field($model, 'province', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-map\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->dropDownList($provinceItems, [
                    'id' => 'ddl-province',
                    'prompt' => 'เลือกจังหวัด',
                ]) ?>
            </div>

            <div class="col-12 col-md-2">
                <?= $form->field($model, 'district', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-signpost-split\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->widget(DepDrop::class, [
                    'options' => ['id' => 'ddl-amphur'],
                    'data' => $amphur,
                    'pluginOptions' => [
                        'depends' => ['ddl-province'],
                        'placeholder' => 'เลือกอำเภอ...',
                        'url' => Url::to(['/researchpro/get-amphur']),
                    ],
                ]) ?>
            </div>

            <div class="col-12 col-md-2">
                <?= $form->field($model, 'sub_district', [
                    'template' => "{label}\n<div class=\"input-group\">\n<span class=\"input-group-text\"><i class=\"bi bi-geo\"></i></span>\n{input}\n</div>\n{hint}\n{error}"
                ])->widget(DepDrop::class, [
                    'data' => $subDistrict,
                    'pluginOptions' => [
                        'depends' => ['ddl-province', 'ddl-amphur'],
                        'placeholder' => 'เลือกตำบล...',
                        'url' => Url::to(['/researchpro/get-district']),
                    ],
                ]) ?>
            </div>
        </div>

    </div>

    <div class="card-footer bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="text-muted small">
            <i class="bi bi-shield-check me-1"></i>
            โปรดตรวจสอบความถูกต้องก่อนบันทึก
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-arrow-left"></i> ย้อนกลับ', ['index'], ['class' => 'btn btn-outline-secondary', 'encode' => false]) ?>
            <?= Html::submitButton('<i class="bi bi-save"></i> บันทึกข้อมูล', ['class' => 'btn btn-success', 'encode' => false]) ?>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>

</div>
