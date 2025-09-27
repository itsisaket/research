<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use miloschuman\highcharts\Highcharts;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $searchModel app\models\ProjectSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="project-index">
<div class="card">
    <div class="card-header bg-gradient-primary">
        <h5><i class="far fa-chart-bar"> ข้อมูลวิจัยแยกรายปี</i></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-9 col-9 text-center">   
                  <?php
                        echo Highcharts::widget([
                            'options' => [
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => $categoriesY
                                ],
                                'yAxis' => [
                                    'title' => ['text' => 'จำนวน(วิจัย)']
                                ],
                                'plotOptions' => [
                                    'series' => [
                                        'borderWidth' => '0',
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
                                ]
                            ]
                        ]);
                    ?>
            </div>
            <div class="col-lg-3 col-3">   
                <div class="small-box bg-gradient-success">
                    <div class="inner">
                        <h5><?= Html::a($countuser, ['/account/index'], ['class' => 'badge badge-light']) ?></h5>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
        
                    <p class="small-box-footer">ข้อมูลนักวิจัย <i class="fas fa-arrow-circle-right"></i> </p> 
                    </a>
                </div>
                <div class="small-box bg-gradient-info">
                    <div class="inner">
                        <h5><?= Html::a($counttype1, ['/account/index'], ['class' => 'badge badge-light']) ?></h5>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
        
                    <p class="small-box-footer">ข้อมูลโครงการวิจัย <i class="fas fa-arrow-circle-right"></i> </p> 
                    </a>
                </div>
                <div class="small-box bg-gradient-warning">
                    <div class="inner">
                        <h5><?= Html::a($counttype2, ['/project/index'], ['class' => 'badge badge-light']) ?></h5>
                    </div>
                    <div class="icon">
                        <i class="fas fa-laptop-house"></i>
                    </div>
        
                    <p class="small-box-footer">ข้อมูลชุดแผนงานวิจัย <i class="fas fa-arrow-circle-right"></i> </p> 
                    </a>
                </div>
                <div class="small-box bg-gradient-info">
                    <div class="inner">
                        <h5><?= Html::a($counttype4, ['/report/index'], ['class' => 'badge badge-light']) ?></h5>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
        
                    <p class="small-box-footer">ข้อมูลบทความวิจัย <i class="fas fa-arrow-circle-right"></i> </p> 
                    </a>
                </div>   
                <div class="small-box bg-gradient-secondary">
                    <div class="inner">
                        <h5><?= Html::a($counttype3, ['/report/index'], ['class' => 'badge badge-light']) ?></h5>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
        
                    <p class="small-box-footer">ข้อมูลบริการวิชาการ <i class="fas fa-arrow-circle-right"></i> </p> 
                    </a>
                </div>                                                             
            </div>
        </div>
    </div>
</div>

<hr>
<div class="project-index">
<div class="card">
    <div class="card-header bg-gradient-primary">
        <h5><i class="far fa-chart-bar"> ข้อมูลวิจัยแยกตามหน่วยงาน</i></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-12 col-12 text-center">
                  <?php
                        echo Highcharts::widget([
                            'options' => [
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => $categoriesO
                                ],
                                'yAxis' => [
                                    'title' => ['text' => 'จำนวน(วิจัย)']
                                ],
                                'plotOptions' => [
                                    'series' => [
                                        'borderWidth' => '0',
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
                                ]
                            ]
                        ]);
                    ?>  
            </div>
        </div>
    </div>
</div>                    
                    
</div>
