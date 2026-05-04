<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;   // ✅ เพิ่มใช้ ActiveForm สำหรับฟอร์มใน Modal

/* @var $this yii\web\View */
/* @var $searchModel app\models\ResearchproSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $importModel app\models\ResearchImportForm */
/* @var $contribCount array */
$contribCount = $contribCount ?? [];

$this->title = 'โครงการวิจัย';
$this->params['breadcrumbs'][] = $this->title;

/* ===== helpers สำหรับ format ===== */
$thMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
             'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

$fmtThaiDate = function ($d) use ($thMonths) {
    if (empty($d)) return null;
    // ลอง parse หลาย format
    $s = trim((string)$d);
    if (strpos($s, ' ') !== false) $s = explode(' ', $s)[0];
    $ts = false;
    foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt !== false) { $ts = $dt->getTimestamp(); break; }
    }
    if ($ts === false) $ts = strtotime($s);
    if ($ts === false) return null;
    $day = (int)date('j', $ts);
    $mon = $thMonths[(int)date('n', $ts)];
    $yr  = (int)date('Y', $ts) + 543;
    return "{$day} {$mon} {$yr}";
};

$fmtThaiDateRange = function ($start, $end) use ($fmtThaiDate) {
    $s = $fmtThaiDate($start);
    $e = $fmtThaiDate($end);
    if (!$s && !$e) return '<span class="text-muted">—</span>';
    return ($s ?: '?') . ' <span class="text-muted">–</span> ' . ($e ?: '?');
};
?>
<div class="researchpro-index">

    <p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>

        <!-- 🔵 ปุ่มเปิด Modal อัปโหลดไฟล์ Excel -->
        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#uploadModal">
            <i class="fas fa-file-upload"></i> อัปโหลดไฟล์ Excel
        </button>
    </p>

    <!-- 🔶 แสดง Flash message ทั่วไป -->
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <!-- 🔶 แสดงรายละเอียด error รายแถวจากการ import (ถ้ามี) -->
    <?php $importErrors = Yii::$app->session->getFlash('importErrors', []); ?>
    <?php if (!empty($importErrors)): ?>
        <div class="alert alert-warning">
            <strong>รายละเอียดข้อผิดพลาดจากไฟล์ Excel:</strong>
            <ul>
                <?php foreach ($importErrors as $row => $errors): ?>
                    <li>
                        แถวที่ <?= Html::encode($row) ?>:
                        <?php foreach ($errors as $attr => $msg): ?>
                            <div>- <?= Html::encode($msg) ?></div>
                        <?php endforeach; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>


    <style>
    /* ==== Researchpro index — clean grid ==== */
    .rp-grid { font-size: .92rem; }
    .rp-grid > thead > tr > th {
        background: #fafbfd;
        color: #64748b;
        font-weight: 500;
        font-size: .82rem;
        border-bottom: 2px solid #e2e8f0;
        padding: .85rem .9rem;
        white-space: nowrap;
    }
    .rp-grid > tbody > tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
    .rp-grid > tbody > tr:hover { background: #fafbfd; }
    .rp-grid > tbody > tr > td { padding: 1rem .9rem; vertical-align: middle; }

    /* ชื่อโครงการ */
    .rp-title {
        color: #0f172a;
        font-weight: 600;
        text-decoration: none;
        line-height: 1.4;
    }
    .rp-title:hover { color: #4f46e5; text-decoration: underline; }

    /* sub-text ใต้ชื่อ */
    .rp-sub {
        margin-top: .3rem;
        font-size: .78rem;
        color: #64748b;
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }
    .rp-sub-item { display: inline-flex; align-items: center; gap: .25rem; }
    .rp-sub-item i { font-size: .75rem; opacity: .8; }

    /* แหล่งทุน */
    .rp-fund {
        font-weight: 500;
        color: #1e293b;
        line-height: 1.3;
        margin-bottom: .25rem;
    }
    .rp-badge {
        display: inline-block;
        padding: .15rem .5rem;
        border-radius: 6px;
        font-size: .72rem;
        font-weight: 500;
        border: 1px solid;
    }
    .rp-badge-internal { background: #ecfeff; color: #0e7490; border-color: #a5f3fc; }
    .rp-badge-external { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

    /* งบประมาณ */
    .rp-budget {
        font-weight: 600;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }

    /* ระยะเวลา */
    .rp-period { color: #475569; font-size: .9rem; }

    /* Mobile */
    @media (max-width: 768px) {
        .rp-grid { font-size: .85rem; }
        .rp-grid > tbody > tr > td { padding: .75rem .6rem; }
    }
</style>

<?php Pjax::begin([
        'id' => 'pjax-researchpro',
        'timeout' => 8000,
        'enablePushState' => true,
        'clientOptions' => ['scrollTo' => false],
    ]); ?>

    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div class="text-muted small">
            พบทั้งหมด <strong><?= number_format($dataProvider->getTotalCount()) ?></strong> รายการ
        </div>
        <?php if (!Yii::$app->user->isGuest && $dataProvider->getTotalCount() > 0): ?>
            <?= Html::a(
                '<i class="fas fa-file-excel me-1"></i> ส่งออก Excel ตามผลค้นหา',
                array_merge(['export'], Yii::$app->request->queryParams),
                [
                    'class'   => 'btn btn-success btn-sm',
                    'encode'  => false,
                    'data-pjax' => 0,
                    'target'  => '_blank',
                    'rel'     => 'noopener',
                    'title'   => 'ดาวน์โหลด Excel ตามตัวกรองและคำค้นปัจจุบัน',
                ]
            ) ?>
        <?php endif; ?>
    </div>

    <div class="ss-grid-wrap">
    <?php if ($dataProvider->getTotalCount() === 0): ?>
        <?= $this->render('@app/views/_shared/_empty_state', [
            'icon'    => 'fa-folder-open',
            'title'   => 'ไม่พบโครงการวิจัยตามเงื่อนไข',
            'message' => 'ลองเปลี่ยนคำค้น หรือกดปุ่ม "ลัด" ด้านบน หรือล้างตัวกรองทั้งหมด',
        ]) ?>
    <?php else: ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle rp-grid'],
        'rowOptions'   => ['class' => 'rp-row'],
        'columns' => [

            // ===== ชื่อโครงการ + sub-text (ผู้บันทึก/ผู้ร่วม) =====
            [
                'attribute' => 'projectNameTH',
                'label' => 'ชื่อโครงการวิจัย',
                'format' => 'raw',
                'contentOptions' => ['style' => 'min-width:280px;'],
                'value' => function ($model) use ($contribCount) {
                    $title = Html::a(
                        Html::encode($model->projectNameTH),
                        ['view', 'projectID' => $model->projectID],
                        ['class' => 'rp-title', 'data-pjax' => 0, 'title' => 'คลิกเพื่อดูรายละเอียด']
                    );

                    $owner = '';
                    if ($model->user) {
                        $name = trim(($model->user->uname ?? '') . ' ' . ($model->user->luname ?? ''));
                        $owner = $name !== '' ? $name : $model->username;
                    }
                    $contribN = (int)($contribCount[(int)$model->projectID] ?? 0);

                    $sub = '<div class="rp-sub">';
                    if ($owner !== '') {
                        $sub .= '<span class="rp-sub-item"><i class="fas fa-user-edit"></i> '
                              . Html::encode($owner) . '</span>';
                    }
                    if ($contribN > 0) {
                        $sub .= '<span class="rp-sub-item"><i class="fas fa-users"></i> '
                              . $contribN . ' ผู้ร่วม</span>';
                    }
                    $sub .= '</div>';

                    return $title . $sub;
                },
            ],

            // ===== ประเภท =====
            [
                'label' => 'ประเภท',
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:130px;'],
                'value' => function ($model) {
                    return $model->restypes->restypename
                        ?? '<span class="text-muted">—</span>';
                },
            ],

            // ===== แหล่งทุน + badge ภายใน/ภายนอก =====
            [
                'label' => 'แหล่งทุน',
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:170px;'],
                'value' => function ($model) {
                    $agency = $model->agencys->fundingAgencyName ?? '';
                    $fund   = $model->resFunds->researchFundName ?? '';

                    // heuristic ภายใน/ภายนอก จากชื่อทุน
                    $badge = '';
                    if ($fund !== '') {
                        if (mb_stripos($fund, 'ภายใน') !== false) {
                            $badge = '<span class="rp-badge rp-badge-internal">ภายใน</span>';
                        } else {
                            $badge = '<span class="rp-badge rp-badge-external">ภายนอก</span>';
                        }
                    }

                    $main = $agency !== '' ? Html::encode($agency)
                          : ($fund !== '' ? Html::encode($fund) : '<span class="text-muted">—</span>');

                    return '<div class="rp-fund">' . $main . '</div>' . $badge;
                },
            ],

            // ===== ปีที่ส่ง =====
            [
                'attribute' => 'projectYearsubmit',
                'label' => 'ปีที่ส่ง',
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:90px;text-align:center;'],
                'headerOptions' => ['style' => 'text-align:center;'],
                'value' => function ($model) {
                    return $model->projectYearsubmit
                        ? '<strong>' . (int)$model->projectYearsubmit . '</strong>'
                        : '<span class="text-muted">—</span>';
                },
            ],

            // ===== ระยะเวลา =====
            [
                'label' => 'ระยะเวลา',
                'format' => 'raw',
                'contentOptions' => ['style' => 'min-width:230px;'],
                'value' => function ($model) use ($fmtThaiDateRange) {
                    return '<span class="rp-period">'
                         . $fmtThaiDateRange($model->projectStartDate, $model->projectEndDate)
                         . '</span>';
                },
            ],

        ],
    ]); ?>
    <?php endif; ?>
    </div>

    <?php Pjax::end(); ?>

</div>

<!-- 🔵 Modal สำหรับอัปโหลดไฟล์ Excel -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="uploadModalLabel">อัปโหลดไฟล์ Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <?php $form = ActiveForm::begin([
            'action'  => ['import'],                         // 🔁 ส่งไป actionImport
            'options' => ['enctype' => 'multipart/form-data']
        ]); ?>

        <?= $form->field($importModel, 'file')->fileInput([
            'accept' => '.xls,.xlsx'
        ]) ?>

        <div class="form-group mt-3">
            <?= Html::submitButton('นำเข้าข้อมูล', ['class' => 'btn btn-success']) ?>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        </div>

        <?php ActiveForm::end(); ?>

      </div>
    </div>
  </div>
</div>
