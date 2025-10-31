<?php

use yii\helpers\Html;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */

$this->title = 'รายงานภาพรวมงานวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="report-index">

    <div class="card">
        <div class="card-header bg-gradient-primary">
            <h5><i class="far fa-chart-bar"></i> ข้อมูลวิจัยแยกรายปี</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- กราฟ -->
                <div class="col-lg-9 col-9 text-center">
                    <?=
                        Highcharts::widget([
                            'options' => [
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => $categoriesY,
                                ],
                                'yAxis' => [
                                    'title' => ['text' => 'จำนวน (โครงการ)'],
                                    'allowDecimals' => false,
                                ],
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
                            ],
                        ]);
                    ?>
                </div>

                <!-- กล่องสรุป -->
                <div class="col-lg-3 col-3">

                    <!-- นักวิจัย -->
                    <div class="small-box bg-gradient-success">
                        <div class="inner">
                            <h5>
                                <?= Html::a($countuser, ['/account/index'], ['class' => 'badge badge-light']) ?>
                            </h5>
                        </div>
                        <div class="icon"><i class="fas fa-chart-pie"></i></div>
                        <p class="small-box-footer">
                            <?= $isSelfRole ? 'ข้อมูลของฉัน' : 'ข้อมูลนักวิจัยทั้งหมด' ?>
                            <i class="fas fa-arrow-circle-right"></i>
                        </p>
                    </div>

                    <!-- ประเภท 1 -->
                    <div class="small-box bg-gradient-info">
                        <div class="inner">
                            <h5>
                                <?= Html::a($counttype1, ['/project/index', 'ProjectSearch[pro_type]' => 1], ['class' => 'badge badge-light']) ?>
                            </h5>
                        </div>
                        <div class="icon"><i class="fas fa-user-friends"></i></div>
                        <p class="small-box-footer">ข้อมูลโครงการวิจัย <i class="fas fa-arrow-circle-right"></i></p>
                    </div>

                    <!-- ประเภท 2 -->
                    <div class="small-box bg-gradient-warning">
                        <div class="inner">
                            <h5>
                                <?= Html::a($counttype2, ['/project/index', 'ProjectSearch[pro_type]' => 2], ['class' => 'badge badge-light']) ?>
                            </h5>
                        </div>
                        <div class="icon"><i class="fas fa-laptop-house"></i></div>
                        <p class="small-box-footer">ข้อมูลชุดแผนงานวิจัย <i class="fas fa-arrow-circle-right"></i></p>
                    </div>

                    <!-- ประเภท 4 -->
                    <div class="small-box bg-gradient-info">
                        <div class="inner">
                            <h5>
                                <?= Html::a($counttype4, ['/project/index', 'ProjectSearch[pro_type]' => 4], ['class' => 'badge badge-light']) ?>
                            </h5>
                        </div>
                        <div class="icon"><i class="fas fa-list-alt"></i></div>
                        <p class="small-box-footer">ข้อมูลบทความวิจัย <i class="fas fa-arrow-circle-right"></i></p>
                    </div>

                    <!-- ประเภท 3 -->
                    <div class="small-box bg-gradient-secondary">
                        <div class="inner">
                            <h5>
                                <?= Html::a($counttype3, ['/project/index', 'ProjectSearch[pro_type]' => 3], ['class' => 'badge badge-light']) ?>
                            </h5>
                        </div>
                        <div class="icon"><i class="fas fa-list-alt"></i></div>
                        <p class="small-box-footer">ข้อมูลบริการวิชาการ <i class="fas fa-arrow-circle-right"></i></p>
                    </div>

                </div><!-- col -->
            </div><!-- row -->
        </div><!-- card-body -->
    </div><!-- card -->

    <hr>

    <div class="card">
        <div class="card-header bg-gradient-primary">
            <h5><i class="far fa-chart-bar"></i> ข้อมูลวิจัยแยกตามตำแหน่ง/หน่วยงาน</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-12 col-12 text-center">
                    <?=
                        Highcharts::widget([
                            'options' => [
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => $categoriesO,
                                ],
                                'yAxis' => [
                                    'title' => ['text' => 'จำนวน (โครงการ)'],
                                    'allowDecimals' => false,
                                ],
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
                            ],
                        ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

</div>
