<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */

$this->title = 'รายละเอียดการนำไปใช้ประโยชน์';
$this->params['breadcrumbs'][] = ['label' => 'การนำไปใช้ประโยชน์', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

// ===== helpers กัน null =====
$safe = function ($value, $fallback = '-') {
    return (isset($value) && $value !== '' && $value !== null) ? $value : $fallback;
};

?>
<div class="utilization-view">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="mb-1">
                <i class="fas fa-chart-line me-2"></i><?= Html::encode($this->title) ?>
            </h3>
            <div class="text-muted small">
                <i class="fas fa-info-circle me-1"></i>ตรวจสอบรายละเอียดก่อนแก้ไข/ลบ
            </div>
        </div>

        <div class="text-muted small">
            <i class="fas fa-hashtag me-1"></i>ID: <?= Html::encode($model->utilization_id) ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

<?php
$isOwner = !Yii::$app->user->isGuest 
    && Yii::$app->user->identity->username === $model->username;
?>

<div class="d-flex justify-content-between align-items-center mb-3">

    <!-- ===== LEFT: Back + Edit ===== -->
    <div class="d-flex gap-2">
        <?= Html::a(
            '<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ',
            ['index'],
            ['class' => 'btn btn-outline-secondary', 'encode' => false]
        ) ?>

        <?php if ($isOwner): ?>
            <?= Html::a(
                '<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล',
                ['update', 'utilization_id' => $model->utilization_id],
                ['class' => 'btn btn-primary', 'encode' => false]
            ) ?>
        <?php endif; ?>
    </div>

    <!-- ===== RIGHT: Delete ===== -->
    <div>
        <?php if ($isOwner): ?>
            <?= Html::a(
                '<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล',
                ['delete', 'utilization_id' => $model->utilization_id],
                [
                    'class' => 'btn btn-danger',
                    'encode' => false,
                    'data' => [
                        'confirm' => 'ยืนยันการลบรายการนี้หรือไม่?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?>
    </div>

</div>


            <!-- ===== Section: ข้อมูลโครงการ ===== -->
            <h5 class="mb-2"><i class="fas fa-file-signature me-1"></i> ข้อมูลโครงการ</h5>
            <hr class="mt-2 mb-3">

            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-bordered table-striped mb-4'],
                'template' => '<tr><th style="width:220px;">{label}</th><td>{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'project_name',
                        'label' => 'ชื่อโครงการ/ผลงาน',
                        'value' => $safe($model->project_name),
                    ],
                ],
            ]) ?>

            <!-- ===== Section: หน่วยงานและรายละเอียด ===== -->
            <h5 class="mb-2"><i class="fas fa-building me-1"></i> หน่วยงานและรายละเอียดการใช้ประโยชน์</h5>
            <hr class="mt-2 mb-3">

            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-bordered table-striped mb-4'],
                'template' => '<tr><th style="width:220px;">{label}</th><td>{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'org_id',
                        'label' => 'หน่วยงาน',
                        'value' => function ($model) use ($safe) {
                            // hasorg relation
                            return $safe($model->hasorg->org_name ?? null);
                        },
                    ],
                    [
                        'attribute' => 'username',
                        'label' => 'นักวิจัย',
                        'value' => function ($model) use ($safe) {
                            // user relation
                            $full = trim(($model->user->uname ?? '') . ' ' . ($model->user->luname ?? ''));
                            return $safe($full !== '' ? $full : null, $safe($model->username ?? null));
                        },
                    ],
                    [
                        'attribute' => 'utilization_type',
                        'label' => 'ประเภทการใช้ประโยชน์',
                        'value' => function ($model) use ($safe) {
                            // utilization relation
                            return $safe($model->utilization->utilization_type_name ?? null, $safe($model->utilization_type ?? null));
                        },
                    ],
                    [
                        'attribute' => 'utilization_date',
                        'label' => 'วันที่ใช้ประโยชน์',
                        'value' => $safe($model->utilization_date),
                    ],
                ],
            ]) ?>

            <!-- ===== Section: สถานที่/พื้นที่ ===== -->
            <h5 class="mb-2"><i class="fas fa-map-marker-alt me-1"></i> สถานที่/พื้นที่ใช้ประโยชน์</h5>
            <hr class="mt-2 mb-3">

            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-bordered table-striped mb-4'],
                'template' => '<tr><th style="width:220px;">{label}</th><td>{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'utilization_add',
                        'label' => 'สถานที่/ที่อยู่',
                        'value' => $safe($model->utilization_add),
                    ],
                    [
                        'attribute' => 'sub_district',
                        'label' => 'ตำบล',
                        'value' => function ($model) use ($safe) {
                            return $safe($model->dist->DISTRICT_NAME ?? null);
                        },
                    ],
                    [
                        'attribute' => 'district',
                        'label' => 'อำเภอ',
                        'value' => function ($model) use ($safe) {
                            return $safe($model->amph->AMPHUR_NAME ?? null);
                        },
                    ],
                    [
                        'attribute' => 'province',
                        'label' => 'จังหวัด',
                        'value' => function ($model) use ($safe) {
                            return $safe($model->prov->PROVINCE_NAME ?? null);
                        },
                    ],
                ],
            ]) ?>

            <!-- ===== Section: รายละเอียด/อ้างอิง ===== -->
            <h5 class="mb-2"><i class="fas fa-align-left me-1"></i> รายละเอียดและเอกสารอ้างอิง</h5>
            <hr class="mt-2 mb-3">

            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-bordered table-striped mb-0'],
                'template' => '<tr><th style="width:220px;">{label}</th><td>{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'utilization_detail',
                        'label' => 'รายละเอียด',
                        'format' => 'ntext',
                        'value' => $safe($model->utilization_detail),
                    ],
                    [
                        'attribute' => 'utilization_refer',
                        'label' => 'เอกสารอ้างอิง/ลิงก์',
                        'format' => 'ntext',
                        'value' => $safe($model->utilization_refer),
                    ],
                ],
            ]) ?>

        </div>

        <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center">
            <div class="text-muted small">
                <i class="fas fa-shield-alt me-1"></i> ข้อมูลแสดงผลจากระบบ
            </div>
            <div class="text-muted small">
                <i class="fas fa-clock me-1"></i> <?= date('d/m/Y H:i') ?>
            </div>
        </div>
    </div>

</div>
