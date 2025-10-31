<?php

use yii\helpers\Html;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */

$this->title = 'รายงานภาพรวมงานวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="pc-content">

    <!-- Header แบบ Berry -->
    <div class="row mb-3 align-items-center">
        <div class="col-auto">
            <h3 class="mb-0"><?= Html::encode($this->title) ?></h3>
            <p class="text-muted mb-0">สรุปข้อมูลโครงการวิจัยจากระบบของคุณ</p>
        </div>
        <div class="col text-right">
            <?= Html::a('<i class="fas fa-download mr-1"></i>Export', ['export'], [
                'class' => 'btn btn-sm btn-primary rounded-lg'
            ]) ?>
            <?= Html::a('<i class="fas fa-sync-alt"></i>', ['index'], [
                'class' => 'btn btn-sm btn-outline-primary rounded-lg ml-1'
            ]) ?>
        </div>
    </div>

    <!-- CARD 1: กราฟปี + small boxes -->
    <div class="card card-berry mb-4">
        <div class="card-header d-flex align-items-center justify-content-between bg-gradient-primary berry-header">
            <h5 class="mb-0 text-white">
                <i class="far fa-chart-bar mr-1"></i> ข้อมูลวิจัยแยกรายปี
            </h5>
            <span class="text-white-50 small">แสดงจำนวนโครงการตามปีงบประมาณ</span>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- กราฟ -->
                <div class="col-lg-9 col-md-8 mb-3 mb-md-0 text-center">
                    <?php
                    echo Highcharts::widget([
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
                                'title' => ['text' => 'จำนวน(วิจัย)']
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
                                    'name' => '',
                                    'data' => $seriesY,
                                ]
                            ],
                            'credits' => ['enabled' => false],
                        ]
                    ]);
                    ?>
                </div>

                <!-- กล่องสรุป -->
                <div class="col-lg-3 col-md-4">
                    <div class="berry-smallbox bg-berry-success mb-3">
                        <div class="inner">
                            <p class="label">ข้อมูลนักวิจัย</p>
                            <h4 class="value">
                                <?= Html::a($countuser, ['/account/index'], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-chart-pie"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-info mb-3">
                        <div class="inner">
                            <p class="label">ข้อมูลโครงการวิจัย</p>
                            <h4 class="value">
                                <?= Html::a($counttype1, ['/account/index'], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-user-friends"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-warning mb-3">
                        <div class="inner">
                            <p class="label">ข้อมูลชุดแผนงานวิจัย</p>
                            <h4 class="value">
                                <?= Html::a($counttype2, ['/project/index'], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-laptop-house"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-info mb-3">
                        <div class="inner">
                            <p class="label">ข้อมูลบทความวิจัย</p>
                            <h4 class="value">
                                <?= Html::a($counttype4, ['/report/index'], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-list-alt"></i></div>
                    </div>

                    <div class="berry-smallbox bg-berry-secondary mb-0">
                        <div class="inner">
                            <p class="label">ข้อมูลบริการวิชาการ</p>
                            <h4 class="value">
                                <?= Html::a($counttype3, ['/report/index'], ['class' => 'badge badge-light']) ?>
                            </h4>
                        </div>
                        <div class="icon"><i class="fas fa-list-alt"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 2: กราฟตามหน่วยงาน -->
    <div class="card card-berry mb-4">
        <div class="card-header d-flex align-items-center justify-content-between bg-gradient-primary berry-header">
            <h5 class="mb-0 text-white"><i class="far fa-chart-bar mr-1"></i> ข้อมูลวิจัยแยกตามหน่วยงาน</h5>
            <span class="text-white-50 small">เปรียบเทียบปริมาณโครงการระหว่างหน่วยงาน</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 text-center">
                    <?php
                    echo Highcharts::widget([
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
                                'title' => ['text' => 'จำนวน(วิจัย)']
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
                                    'name' => '',
                                    'data' => $seriesO,
                                ]
                            ],
                            'credits' => ['enabled' => false],
                        ]
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

</div>
