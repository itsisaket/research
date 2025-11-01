<?php

use yii\helpers\Html;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */

$this->title = 'รายงานภาพรวมงานวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pc-content report-index">

    <!-- แถวบน -->
    <div class="mui-grid">
        <div class="mui-item full-purple">
            <div>
                <div class="mui-title">นักวิจัยทั้งหมด</div>
                <div class="mui-value">
                    <?= Html::a($countuser, ['/account/index'], ['class' => 'text-white text-decoration-none']) ?>
                </div>
                <div class="mui-sub">บัญชีที่เข้าใช้งานระบบ</div>
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
                <div class="mui-sub">ประเภท 1</div>
            </div>
            <div class="mui-icon">
                <i class="fas fa-flask"></i>
            </div>
        </div>

        <div class="mui-item full-red">
            <div>
                <div class="mui-title">บทความวิจัย</div>
                <div class="mui-value">
                    <?= Html::a($counttype4, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 4], ['class' => 'text-white text-decoration-none']) ?>
                </div>
                <div class="mui-sub">เผยแพร่แล้ว</div>
            </div>
            <div class="mui-icon">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>

        <div class="mui-item">
            <div>
                <div class="mui-title">ชุดแผนงานวิจัย</div>
                <div class="mui-value">
                    <?= Html::a($counttype2, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 2], ['class' => 'text-decoration-none']) ?>
                </div>
                <div class="mui-sub">รวมทุกหน่วยงาน</div>
            </div>
            <div class="mui-icon blue">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        <div class="mui-item">
            <div>
                <div class="mui-title">บริการวิชาการ</div>
                <div class="mui-value">
                    <?= Html::a($counttype3, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 3], ['class' => 'text-decoration-none']) ?>
                </div>
                <div class="mui-sub">ปีล่าสุด</div>
            </div>
            <div class="mui-icon green">
                <i class="fas fa-hands-helping"></i>
            </div>
        </div>
    </div>

    <!-- CARD 1: กราฟปี -->
    <div class="card dashboard-card mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white">
                <i class="far fa-chart-bar mr-1"></i> ข้อมูลวิจัยแยกรายปี
            </h5>
            <span class="text-white-50 small">จากฐานข้อมูลโครงการวิจัย (tb_researchpro)</span>
        </div>
        <div class="card-body">
            <div class="row align-items-stretch">
                <div class="col-lg-9 col-12 mb-3 mb-lg-0 text-center">
                    <?= Highcharts::widget([
                        'options' => [
                            'chart' => [
                                'height' => 360,
                                'backgroundColor' => 'transparent',
                            ],
                            'title' => ['text' => ''],
                            'xAxis' => [
                                'categories' => $categoriesY
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
                            <small>ประเภท 1</small>
                        </div>
                        <div class="icon"><i class="fas fa-user-friends"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-warning">
                        <div class="inner">
                            <p class="label mb-1">ชุดแผนงาน</p>
                            <h4 class="value mb-0">
                                <?= Html::a($counttype2, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 2], ['class' => 'badge badge-light']) ?>
                            </h4>
                            <small>ประเภท 2</small>
                        </div>
                        <div class="icon"><i class="fas fa-laptop-house"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-secondary mb-0">
                        <div class="inner">
                            <p class="label mb-1">บริการวิชาการ</p>
                            <h4 class="value mb-0">
                                <?= Html::a($counttype3, ['/researchpro/index', 'ResearchproSearch[researchTypeID]' => 3], ['class' => 'badge badge-light']) ?>
                            </h4>
                            <small>ประเภท 3</small>
                        </div>
                        <div class="icon"><i class="fas fa-list-alt"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD: สรุป 5 ประเด็น -->
    <div class="card dashboard-card mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white">
                <i class="fas fa-table mr-1"></i> สรุปข้อมูลโครงการตามหัวข้อหลัก
            </h5>
            <span class="text-white-50 small">งบประมาณ / ประเภทโครงการ / ประเภทการวิจัย / สถานะงาน / แหล่งทุน</span>
        </div>
        <div class="card-body">
            <div class="row">

                <!-- 1) งบประมาณรวม -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-success">
                        <div class="inner">
                            <p class="label mb-1">งบประมาณรวม</p>
                            <h4 class="value mb-0">
                                <?= number_format($totalBudgets, 0) ?> บาท
                            </h4>
                            <small>เฉพาะโครงการที่คุณมีสิทธิ์เห็น</small>
                        </div>
                        <div class="icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>

                <!-- 2) ประเภทโครงการ -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-info">
                        <div class="inner">
                            <p class="label mb-1">ประเภทโครงการ</p>
                            <?php if (!empty($typeData)): ?>
                                <?php foreach ($typeData as $tid => $cnt): ?>
                                    <?php
                                        $label = $restypeMap[$tid]['restypename'] ?? ('ประเภท ' . $tid);
                                    ?>
                                    <div><?= Html::encode($label) ?> : <?= Html::encode($cnt) ?> โครงการ</div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                        <div class="icon"><i class="fas fa-list"></i></div>
                    </div>
                </div>

                <!-- 3) ประเภทการวิจัย -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-warning">
                        <div class="inner">
                            <p class="label mb-1">ประเภทการวิจัย</p>
                            <?php if (!empty($fundData)): ?>
                                <?php foreach ($fundData as $fid => $cnt): ?>
                                    <?php
                                        $label = $resfundMap[$fid]['researchFundName'] ?? ('ทุน ' . $fid);
                                    ?>
                                    <div><?= Html::encode($label) ?> : <?= Html::encode($cnt) ?> โครงการ</div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                        <div class="icon"><i class="fas fa-flask"></i></div>
                    </div>
                </div>

                <!-- 4) สถานะงาน -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-secondary">
                        <div class="inner">
                            <p class="label mb-1">สถานะงาน</p>
                            <?php if (!empty($statusData)): ?>
                                <?php foreach ($statusData as $sid => $cnt): ?>
                                    <?php
                                        $label = $resstatusMap[$sid]['statusname'] ?? ('สถานะ ' . $sid);
                                    ?>
                                    <div><?= Html::encode($label) ?> : <?= Html::encode($cnt) ?> โครงการ</div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                        <div class="icon"><i class="fas fa-tasks"></i></div>
                    </div>
                </div>

                <!-- 5) แหล่งทุน -->
                <div class="col-md-3 col-12 mb-3">
                    <div class="berry-smallbox bg-berry-info">
                        <div class="inner">
                            <p class="label mb-1">แหล่งทุน</p>
                            <?php if (!empty($agencyData)): ?>
                                <?php foreach ($agencyData as $aid => $cnt): ?>
                                    <?php
                                        $label = $agencyMap[$aid]['fundingAgencyName'] ?? ('แหล่งทุน ' . $aid);
                                    ?>
                                    <div><?= Html::encode($label) ?> : <?= Html::encode($cnt) ?> โครงการ</div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- CARD 2: กราฟหน่วยงาน -->
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

</div>
