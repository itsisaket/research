<?php

use yii\helpers\Html;
use yii\helpers\Url;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */
/* @var $seriesY array */
/* @var $budgetSeriesY array */
/* @var $categoriesY array */
/* @var $seriesO array */
/* @var $categoriesO array */
/* @var $counttype1 int|string */
/* @var $counttype2 int|string */
/* @var $counttype3 int|string */
/* @var $counttype4 int|string */
/* @var $countuser int|string */
/* @var $isSelfRole bool */
/* @var $totalBudgets int|float */
/* @var $typeData array */
/* @var $fundData array */
/* @var $statusData array */
/* @var $agencyData array */
/* @var $restypeMap array */
/* @var $resfundMap array */
/* @var $resstatusMap array */
/* @var $agencyMap array */
/* @var $fundingSeries array */
/* @var $fundingTotalNonZero array */
/* @var $yearFrom int */
/* @var $yearTo int */

$this->title = 'รายงานภาพรวมงานวิจัย';
$this->params['breadcrumbs'][] = $this->title;

$currentYearTH = (int)date('Y') + 543;
$yearOptions = [];
for ($y = $currentYearTH; $y >= $currentYearTH - 15; $y--) {
    $yearOptions[$y] = $y;
}
?>
<div class="pc-content report-index">

    <!-- Page header + Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <h1 class="h3 mb-1 text-primary">ระบบสารสนเทศงานวิจัย เพื่อการบริหารจัดการ</h1>
                        <div class="text-muted">LASC SSKRU Research Management</div>
                    </div>
                </div>

                <form method="get" action="<?= Url::to(['report/index']) ?>" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-1">ปีเริ่มต้น</label>
                        <?= Html::dropDownList('year_from', $yearFrom ?? ($currentYearTH - 4), $yearOptions, [
                            'class' => 'form-select'
                        ]) ?>
                    </div>

                    <div class="col-auto">
                        <label class="form-label mb-1">ปีสิ้นสุด</label>
                        <?= Html::dropDownList('year_to', $yearTo ?? $currentYearTH, $yearOptions, [
                            'class' => 'form-select'
                        ]) ?>
                    </div>

                    <div class="col-auto">
                        <?= Html::submitButton('<i class="fas fa-search me-1"></i> ค้นหา', [
                            'class' => 'btn btn-primary',
                            'encode' => false,
                        ]) ?>
                    </div>

                    <div class="col-auto">
                        <?= Html::a('<i class="fas fa-sync-alt me-1"></i> ล้างค่า', ['index'], [
                            'class' => 'btn btn-outline-secondary',
                            'encode' => false,
                        ]) ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- แถวบน -->
    <div class="mui-grid">
        <div class="mui-item full-purple">
            <div>
                <div class="mui-title">นักวิจัยทั้งหมด</div>
                <div class="mui-value">
                    <?= Html::a($countuser, ['/account/index'], ['class' => 'text-white text-decoration-none']) ?>
                </div>
            </div>
            <div class="mui-icon">
                <i class="fas fa-user-friends"></i>
            </div>
        </div>

        <div class="mui-item full-blue">
            <div>
                <div class="mui-title">โครงการวิจัย</div>
                <div class="mui-value">
                    <?= Html::a($counttype1, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 1], ['class' => 'text-white text-decoration-none']) ?>
                </div>
            </div>
            <div class="mui-icon">
                <i class="fas fa-flask"></i>
            </div>
        </div>

        <div class="mui-item full-red">
            <div>
                <div class="mui-title">บทความวิจัย</div>
                <div class="mui-value">
                    <?= Html::a($counttype4, ['/article/index'], ['class' => 'text-white text-decoration-none']) ?>
                </div>
            </div>
            <div class="mui-icon">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>

        <div class="mui-item">
            <div>
                <div class="mui-title">บริการวิชาการ</div>
                <div class="mui-value">
                    <?= Html::a($counttype3, ['/academic-service/index'], ['class' => 'text-decoration-none']) ?>
                </div>
            </div>
            <div class="mui-icon green">
                <i class="fas fa-hands-helping"></i>
            </div>
        </div>
    </div>

    <!-- CARD 1: กราฟจำนวนโครงการรายปี -->
    <div class="card dashboard-card mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white">
                <i class="far fa-chart-bar mr-1"></i> ข้อมูลวิจัยแยกรายปี
            </h5>
            <span class="text-white-50 small">
                จากฐานข้อมูลโครงการวิจัย (tb_researchpro) | ช่วงปี <?= Html::encode($yearFrom) ?> - <?= Html::encode($yearTo) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row align-items-stretch">
                <div class="col-lg-9 col-12 mb-3 mb-lg-0 text-center">
                    <?= Highcharts::widget([
                        'options' => [
                            'accessibility' => ['enabled' => false],
                            'chart' => [
                                'height' => 360,
                                'backgroundColor' => 'transparent',
                            ],
                            'title' => ['text' => ''],
                            'xAxis' => [
                                'categories' => $categoriesY,
                                'title' => ['text' => 'ปี พ.ศ.'],
                            ],
                            'yAxis' => [
                                'title' => ['text' => 'จำนวน(โครงการ)'],
                                'allowDecimals' => false,
                                'min' => 0,
                            ],
                            'plotOptions' => [
                                'series' => [
                                    'borderWidth' => 0,
                                    'dataLabels' => [
                                        'enabled' => true,
                                    ]
                                ]
                            ],
                            'series' => [
                                [
                                    'type' => 'column',
                                    'colorByPoint' => true,
                                    'name' => 'จำนวนโครงการ',
                                    'data' => $seriesY,
                                ]
                            ],
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>

                <div class="col-lg-3 col-12">
                    <div class="berry-smallbox bg-berry-success">
                        <div class="inner">
                            <p class="label mb-1">นักวิจัย</p>
                            <h4 class="value mb-0">
                                <?= Html::a($countuser, ['/account/index'], ['class' => 'badge badge-light']) ?>
                            </h4>
                            <small>ผู้ใช้ในระบบ</small>
                        </div>
                        <div class="icon"><i class="fas fa-chart-pie"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-info">
                        <div class="inner">
                            <p class="label mb-1">โครงการวิจัย</p>
                            <h4 class="value mb-0">
                                <?= Html::a($counttype1, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 1], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-user-friends"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-warning">
                        <div class="inner">
                            <p class="label mb-1">ชุดแผนงาน</p>
                            <h4 class="value mb-0">
                                <?= Html::a($counttype2, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 2], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-laptop-house"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-secondary mb-0">
                        <div class="inner">
                            <p class="label mb-1">บริการวิชาการ</p>
                            <h4 class="value mb-0">
                                <?= Html::a($counttype3, ['/academic-service/index'], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-list-alt"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD: สรุปข้อมูล -->
    <div class="card dashboard-card mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white">
                <i class="fas fa-table mr-1"></i> สรุปข้อมูลโครงการตามหัวข้อหลัก
            </h5>
            <span class="text-white-50 small">
                งบประมาณ / ประเภทการวิจัย / ประเภททุน / สถานะงาน | ช่วงปี <?= Html::encode($yearFrom) ?> - <?= Html::encode($yearTo) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">

                <!-- งบประมาณรวม -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-success">
                        <div class="inner">
                            <p class="label mb-1">งบประมาณรวม</p>
                            <h4 class="value mb-0"><?= number_format((float)$totalBudgets, 0) ?> บาท</h4>
                            <small>เฉพาะโครงการที่คุณมีสิทธิ์เห็น</small>
                        </div>
                        <div class="icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>

                <!-- ประเภทการวิจัย -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-info">
                        <div class="inner">
                            <p class="label mb-1">ประเภทการวิจัย</p>
                            <?php if (!empty($typeData)): ?>
                                <?php foreach ($typeData as $tid => $cnt): ?>
                                    <?php $label = $restypeMap[$tid]['restypename'] ?? ('ประเภท ' . $tid); ?>
                                    <div><?= Html::encode($label) ?> : <?= Html::encode($cnt) ?> โครงการ</div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                        <div class="icon"><i class="fas fa-project-diagram"></i></div>
                    </div>
                </div>

                <!-- ประเภททุนวิจัย -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-warning">
                        <div class="inner">
                            <p class="label mb-1">ประเภททุนวิจัย</p>
                            <?php if (!empty($fundData)): ?>
                                <?php foreach ($fundData as $fid => $cnt): ?>
                                    <?php $label = $resfundMap[$fid]['researchFundName'] ?? ('ทุน ' . $fid); ?>
                                    <div><?= Html::encode($label) ?> : <?= Html::encode($cnt) ?> โครงการ</div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                        <div class="icon"><i class="fas fa-flask"></i></div>
                    </div>
                </div>

                <!-- สถานะงาน -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-secondary">
                        <div class="inner">
                            <p class="label mb-1">สถานะงาน</p>
                            <?php if (!empty($statusData)): ?>
                                <?php foreach ($statusData as $sid => $cnt): ?>
                                    <?php $label = $resstatusMap[$sid]['statusname'] ?? ('สถานะ ' . $sid); ?>
                                    <div><?= Html::encode($label) ?> : <?= Html::encode($cnt) ?> โครงการ</div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                        <div class="icon"><i class="fas fa-tasks"></i></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- CARD: งบประมาณรายปี -->
    <div class="card dashboard-card mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white">
                <i class="fas fa-chart-line mr-1"></i> งบประมาณรายปี (บาท)
            </h5>
            <span class="text-white-50 small">แนวโน้มงบประมาณรวมของโครงการในแต่ละปี</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 text-center">
                    <?= Highcharts::widget([
                        'options' => [
                            'accessibility' => ['enabled' => false],
                            'chart' => [
                                'type' => 'spline',
                                'height' => 400,
                                'backgroundColor' => 'transparent',
                            ],
                            'title' => ['text' => 'แนวโน้มงบประมาณรวมของโครงการในช่วงปีที่เลือก'],
                            'xAxis' => [
                                'categories' => $categoriesY,
                                'crosshair'  => true,
                                'title' => ['text' => 'ปี พ.ศ.'],
                            ],
                            'yAxis' => [
                                'title' => ['text' => 'งบประมาณ (บาท)'],
                                'min' => 0,
                                'labels' => [
                                    'formatter' => new \yii\web\JsExpression("function() { return this.value.toLocaleString(); }")
                                ],
                            ],
                            'tooltip' => [
                                'shared' => true,
                                'pointFormat' => '<b>{point.y:,.0f}</b> บาท',
                            ],
                            'plotOptions' => [
                                'spline' => [
                                    'lineWidth' => 3,
                                    'marker' => [
                                        'enabled' => true,
                                        'radius' => 5,
                                    ],
                                    'dataLabels' => [
                                        'enabled' => true,
                                        'format' => '{point.y:,.0f}',
                                    ],
                                ],
                            ],
                            'series' => [
                                [
                                    'name' => 'งบประมาณรวมต่อปี',
                                    'data' => $budgetSeriesY,
                                    'color' => '#f59e0b',
                                ],
                            ],
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD: แหล่งทุนรายปี -->
    <div class="card dashboard-card mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white">
                <i class="fas fa-sitemap mr-1"></i> แหล่งทุนรายปี
            </h5>
            <span class="text-white-50 small">จำนวนโครงการในแต่ละแหล่งทุน (เฉพาะที่มีโครงการ)</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-9 col-12 mb-3 mb-lg-0 text-center">
                    <?= Highcharts::widget([
                        'options' => [
                            'accessibility' => ['enabled' => false],
                            'chart' => [
                                'type' => 'column',
                                'height' => 420,
                                'backgroundColor' => 'transparent',
                            ],
                            'title' => ['text' => ''],
                            'xAxis' => [
                                'categories' => $categoriesY,
                                'crosshair'  => true,
                                'title' => ['text' => 'ปี พ.ศ.'],
                            ],
                            'yAxis' => [
                                'min'   => 0,
                                'allowDecimals' => false,
                                'title' => ['text' => 'จำนวนโครงการ'],
                            ],
                            'tooltip' => [
                                'shared' => true,
                            ],
                            'plotOptions' => [
                                'column' => [
                                    'dataLabels' => [
                                        'enabled' => true,
                                    ]
                                ]
                            ],
                            'series'  => $fundingSeries,
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>

                <div class="col-lg-3 col-12">
                    <div class="berry-smallbox bg-berry-warning" style="min-height: 100%;">
                        <div class="inner">
                            <p class="label mb-1">แหล่งทุนที่มีโครงการ</p>
                            <?php if (!empty($fundingTotalNonZero)): ?>
                                <?php foreach ($fundingTotalNonZero as $ag): ?>
                                    <div>
                                        <?= Html::encode($ag['name']) ?> :
                                        <span class="badge badge-light"><?= (int)$ag['total'] ?> โครงการ</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                            <small class="d-block mt-2 text-muted">
                                แสดงเฉพาะแหล่งทุนที่มีโครงการในช่วงปีที่เลือก
                            </small>
                        </div>
                        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php /*
    <!-- CARD: กราฟหน่วยงาน -->
    <div class="card dashboard-card mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white">
                <i class="far fa-chart-bar mr-1"></i> ข้อมูลวิจัยแยกตามหน่วยงาน
            </h5>
            <span class="text-white-50 small">เปรียบเทียบจำนวนโครงการตาม org_id</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 text-center">
                    <?= Highcharts::widget([
                        'options' => [
                            'accessibility' => ['enabled' => false],
                            'chart' => [
                                'height' => 420,
                                'backgroundColor' => 'transparent',
                            ],
                            'title' => ['text' => ''],
                            'xAxis' => [
                                'categories' => $categoriesO
                            ],
                            'yAxis' => [
                                'title' => ['text' => 'จำนวน(โครงการ)']
                            ],
                            'plotOptions' => [
                                'series' => [
                                    'borderWidth' => 0,
                                    'dataLabels' => [
                                        'enabled' => true,
                                    ]
                                ]
                            ],
                            'series' => [
                                [
                                    'type' => 'spline',
                                    'colorByPoint' => true,
                                    'name' => 'จำนวนโครงการ',
                                    'data' => $seriesO,
                                ]
                            ],
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
    */ ?>

</div>