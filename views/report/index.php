<?php

use yii\helpers\Html;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */
/* @var $categoriesY array */
/* @var $seriesY array */
/* @var $categoriesO array */
/* @var $seriesO array */
/* @var $countuser mixed */
/* @var $counttype1 integer */
/* @var $counttype2 integer */
/* @var $counttype3 integer */
/* @var $counttype4 integer */
/* @var $isSelfRole boolean */

$this->title = 'รายงานภาพรวมงานวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="pc-content report-index">

    <!-- ส่วนหัวแบบ Berry -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-6 col-12">
            <h3 class="mb-0"><?= Html::encode($this->title) ?></h3>
            <small class="text-muted-soft">แดชบอร์ดสรุปผลโครงการวิจัยจากระบบ LASC SSKRU</small>
        </div>
        <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
            <?= Html::a('<i class="fas fa-download"></i> ส่งออกข้อมูล', ['export'], [
                'class' => 'btn btn-sm btn-outline-primary rounded-xl'
            ]) ?>
            <?= Html::a('<i class="fas fa-sync-alt"></i>', ['index'], [
                'class' => 'btn btn-sm btn-light border ml-1 rounded-xl'
            ]) ?>
        </div>
    </div>

    <!-- แถวบน: ตัวเลขสำคัญ -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-gradient-success shadow-soft">
                <div class="inner">
                    <p class="mb-1"><?= $isSelfRole ? 'ข้อมูลของฉัน' : 'นักวิจัยทั้งหมด' ?></p>
                    <h4 class="mb-0">
                        <?= Html::a($countuser, ['/account/index'], ['class' => 'badge badge-light']) ?>
                    </h4>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
                <p class="small-box-footer">ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i></p>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-gradient-info shadow-soft">
                <div class="inner">
                    <p class="mb-1">โครงการวิจัย</p>
                    <h4 class="mb-0">
                        <?= Html::a($counttype1, ['/project/index', 'ProjectSearch[pro_type]' => 1], ['class' => 'badge badge-light']) ?>
                    </h4>
                </div>
                <div class="icon"><i class="fas fa-flask"></i></div>
                <p class="small-box-footer">เข้าระบบโครงการ <i class="fas fa-arrow-circle-right"></i></p>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-gradient-warning shadow-soft">
                <div class="inner">
                    <p class="mb-1">ชุดแผนงานวิจัย</p>
                    <h4 class="mb-0">
                        <?= Html::a($counttype2, ['/project/index', 'ProjectSearch[pro_type]' => 2], ['class' => 'badge badge-light']) ?>
                    </h4>
                </div>
                <div class="icon"><i class="fas fa-project-diagram"></i></div>
                <p class="small-box-footer">ดูทั้งหมด <i class="fas fa-arrow-circle-right"></i></p>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-gradient-secondary shadow-soft">
                <div class="inner">
                    <p class="mb-1">บริการวิชาการ</p>
                    <h4 class="mb-0">
                        <?= Html::a($counttype3, ['/project/index', 'ProjectSearch[pro_type]' => 3], ['class' => 'badge badge-light']) ?>
                    </h4>
                </div>
                <div class="icon"><i class="fas fa-hands-helping"></i></div>
                <p class="small-box-footer">ดูข้อมูลบริการ <i class="fas fa-arrow-circle-right"></i></p>
            </div>
        </div>
    </div>

    <!-- CARD หลัก 1 : กราฟรายปี + กล่องด้านข้าง -->
    <div class="card shadow-soft rounded-xl mb-4">
        <div class="card-header bg-gradient-primary d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="far fa-chart-bar mr-1"></i> ข้อมูลวิจัยแยกรายปี</h5>
            <span class="text-white-50 small">แสดงจำนวนโครงการต่อปีงบประมาณ</span>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- กราฟ -->
                <div class="col-lg-9 col-md-8 mb-3 mb-md-0 text-center">
                    <?=
                        Highcharts::widget([
                            'options' => [
                                'chart' => [
                                    'height' => 380,
                                ],
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => $categoriesY,
                                    'labels' => [
                                        'style' => [
                                            'fontFamily' => 'Prompt, sans-serif',
                                        ],
                                    ],
                                ],
                                'yAxis' => [
                                    'title' => ['text' => 'จำนวน (โครงการ)'],
                                    'allowDecimals' => false,
                                ],
                                'legend' => ['enabled' => false],
                                'plotOptions' => [
                                    'series' => [
                                        'borderWidth' => 0,
                                        'dataLabels' => [
                                            'enabled' => true,
                                        ],
                                    ],
                                ],
                                'series' => [
                                    [
                                        'type' => 'column',
                                        'colorByPoint' => true,
                                        'name' => 'จำนวนโครงการ',
                                        'data' => $seriesY,
                                    ],
                                ],
                                'credits' => ['enabled' => false],
                            ],
                        ]);
                    ?>
                </div>

                <!-- กล่องสรุปด้านขวา -->
                <div class="col-lg-3 col-md-4">
                    <div class="small-box bg-gradient-info mb-3">
                        <div class="inner">
                            <p class="mb-1">บทความวิจัย</p>
                            <h4 class="mb-0">
                                <?= Html::a($counttype4, ['/project/index', 'ProjectSearch[pro_type]' => 4], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                        <p class="small-box-footer">เปิดดูบทความ <i class="fas fa-arrow-circle-right"></i></p>
                    </div>

                    <div class="small-box bg-gradient-success mb-3">
                        <div class="inner">
                            <p class="mb-1">โครงการทั้งหมด (ปีล่าสุด)</p>
                            <h4 class="mb-0">
                                <?= array_sum($seriesY) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-layer-group"></i></div>
                        <p class="small-box-footer">รวมทุกประเภท</p>
                    </div>

                    <div class="small-box bg-gradient-secondary mb-0">
                        <div class="inner">
                            <p class="mb-1">โครงการเฉลี่ย/ปี</p>
                            <h4 class="mb-0">
                                <?php
                                $avg = count($seriesY) ? round(array_sum($seriesY) / count($seriesY), 1) : 0;
                                echo $avg;
                                ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                        <p class="small-box-footer">แนวโน้มการดำเนินงาน</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- CARD หลัก 2 : กราฟตามตำแหน่ง / หน่วยงาน -->
    <div class="card shadow-soft rounded-xl mb-4">
        <div class="card-header bg-gradient-primary d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="far fa-chart-bar mr-1"></i> ข้อมูลวิจัยแยกตามตำแหน่ง / หน่วยงาน</h5>
            <span class="text-white-50 small">เปรียบเทียบปริมาณโครงการระหว่างกลุ่ม</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 text-center">
                    <?=
                        Highcharts::widget([
                            'options' => [
                                'chart' => [
                                    'height' => 420,
                                ],
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => $categoriesO,
                                    'labels' => [
                                        'rotation' => -25,
                                        'style' => [
                                            'fontFamily' => 'Prompt, sans-serif',
                                            'fontSize'   => '11px',
                                        ],
                                    ],
                                ],
                                'yAxis' => [
                                    'title' => ['text' => 'จำนวน (โครงการ)'],
                                    'allowDecimals' => false,
                                ],
                                'legend' => ['enabled' => false],
                                'plotOptions' => [
                                    'series' => [
                                        'borderWidth' => 0,
                                        'dataLabels' => [
                                            'enabled' => true,
                                        ],
                                    ],
                                ],
                                'series' => [
                                    [
                                        'type' => 'spline',
                                        'colorByPoint' => true,
                                        'name' => 'จำนวนโครงการ',
                                        'data' => $seriesO,
                                    ],
                                ],
                                'credits' => ['enabled' => false],
                            ],
                        ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

</div>
