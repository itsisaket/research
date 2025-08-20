<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

use kartik\widgets\Select2;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\DepDrop;
use kartik\widgets\FileInput;
use kartik\date\DatePicker;


use yii\bootstrap4\Alert;
use yii\bootstrap4\Modal;

use miloschuman\highcharts\Highcharts;
use yii\web\JsExpression;

use function PHPSTORM_META\type;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use miloschuman\highcharts\HighchartsAsset;
HighchartsAsset::register($this)->withScripts(['modules/exporting']);



/** @var yii\web\View $this */

$this->title = '';

$test=[['name'=>'123','data'=>[11,21,31]],['name'=>'567','data'=>[18,8,6]]];
//print_r ($test);
?>
<div class="site-index">

    <div class="jumbotron text-center bg-transparent">
        <div class="row justify-content-center">
            <table>
                <tbody>
                    <tr>
                    <th><?= Html::img('@web/img/'.$model->org_id.'.png');?></th>
                    <th>
                        <h1 class="display-4"><?=$model->org_name;?></h1>
                        <p class="lead">ระบบสารสนเทศงานวิจัย เพื่อการบริหารจัดการ</p>
                    </th>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="row d-flex justify-content-center">
                        <form class="form-inline" action="site/report1">
                        
                            <div class="input-group">
                            <select class="form-control" name="resyear">
                                <?php  foreach ($categoriesResyear as $Org) { ?>
                                    <option><?=$Org;?></option>
                                <?php }                                 ?>
                            </select>
                                <div class="input-group-append">
                                    <button class="btn btn-success" type="submit">Go</button>
                                </div>
                            </div>
                        </form>
                    </div>
    </div>
    <div class="body-content">
        <div class="row">
            <div class="col-lg-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?=$countProject;?></h3>
                        <p> โครงการวิจัย/บริการวิชาการ</p>
                    </div>
                    <div class="icon">
                        <i class="far fa-clone"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?=$countArticle;?></h3>
                        <p>บทความวารสาร</p>
                    </div>
                    <div class="icon">
                        <i class="far fa-compass"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?=$countuser;?></h3>
                        <p>นักวิจัย</p>
                    </div>
                    <div class="icon">
                        <i class="far fa-user"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?=$countUtilization;?></h3>
                        <p>นำไปใช้ประโยชน์</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-flag"></i>
                    </div>
                </div>
            </div>
        </div>
    </div> 

<?php 
    $session = Yii::$app->session;
    if($session['ty']==11 ){    
?>
    
    <div class="row">
            <div class="col-lg-12 ">   
                <div class="card">
            
                <?php
                
                        echo Highcharts::widget([
                            'options' => [
                                'chart'=>[
                                    'type'=>'column',
                                    'colorByPoint' => true, 
                                    //'height'=> 500,    
                                ],
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => $categoriesOrganize,
                                    'labels'=>[
                                        'rotation'=> 0,
                                    ],
                                ],
                                'yAxis' => [
                                    'title' => ['text' => 'จำนวน()']
                                ],
                                'plotOptions' => [
                                    'series' => [
                                        'borderWidth' => '0',
                                        'dataLabels' => [
                                            'enabled' => true,
                                        ],
                                    ],
                                    'column'=>[
                                        'borderWidth' => '0',
                                        'dataLabels' => [
                                            'enabled' => true,
                                        ],

                                    ],
                                ],
                                'series' =>$seriesOrganize,

                            ]
                        ]);
                    ?>
                </div>
            </div>
    </div>
<?php } ?>
<hr>
 
    <div class="row">
        <div class="col-lg-6 ">  
                <div class="card">
                    <div class="card-header bg-gradient-info">
                    <h5><i class="far fa-chart-bar"> ข้อมูลรายปี</i></h5>
                    
                    </div>
                    <div class="card-body">
                        <?php
                        
                                echo Highcharts::widget([
                                    'options' => [
                                        'chart'=>[
                                            'type'=>'spline',
                                            'colorByPoint' => true,
                                        // 'height'=> 400,
                                        ],
                                        'title' => ['text' => ''],
                                        'xAxis' => [
                                            'categories' => $categoriesResyear,
                                        ],
                                        'yAxis' => [
                                            'title' => ['text' => 'จำนวน']
                                        ],
                                        'plotOptions' => [
                                            'series' => [
                                                'borderWidth' => '0',
                                                'dataLabels' => [
                                                    'enabled' => true,
                                                ]
                                            ]
                                        ],
                                        
                                        'series' =>$seriesResyear,

                                    ]
                                ]);
                            ?>
                    </div>
                    
                </div>
        </div>

        <div class="col-lg-6  ">  
                <div class="card">
                    <div class="card-header bg-gradient-success">
                        <h5><i class="far fa-chart-bar"> ข้อมูลแหล่งทุน</i></h5>
                    </div>
                    <div class="card-body">
                            <?php
                                echo Highcharts::widget([
                                    'options' => [
                                        'chart'=>[
                                            'type'=>'bar',
                                            'colorByPoint' => true, 
                                            //'height'=> 600,    
                                        ],
                                        'xAxis' => [
                                            'categories' => $categoriesResGency,
                                            'labels'=>[
                                                'rotation'=> 0,
                                            ],
                                        ],
                                        'yAxis' => [
                                            'title' => ['text' => 'จำนวน']
                                        ],
                                        'title' => ['text' => ''],
                                        'plotOptions' => [
                                            'series' => [
                                                'borderWidth' => '0',
                                                'dataLabels' => [
                                                    'enabled' => true,
                                                ],
                                            ],
                                            'column'=>[
                                                'borderWidth' => '0',
                                                'dataLabels' => [
                                                    'enabled' => true,
                                                ],
                                            ],
                                        ],
                                        'series' =>[
                                            [
                                                'name'=>'โครงการวิจัย',
                                                'data'=>$pieResGency,
                                            ]
                                        ]  
                                    ]
                                ]);
                        ?>
                    </div>
                </div>
        </div>
    </div>
<hr>
   
    <div class="row">
            <div class="col-lg-12 ">   
                <div class="card">    
                    <div class="card-header bg-gradient-success">
                        <h5><i class="far fa-chart-bar"> ข้อมูลงบประมาณ(แเหล่งทุน)</i></h5>
                    </div> 
                    <div class="card-body">
                        <?php
                                echo Highcharts::widget([
                                    'options' => [
                                        'chart'=>[
                                            'type'=>'spline',
                                            'colorByPoint' => true, 
                                            'height'=> 600,    
                                        ],
                                        'title' => ['text' => ''],
                                        'xAxis' => [
                                            'categories' => $categoriesResGency,
                                            'labels'=>[
                                                'rotation'=> 0,
                                            ],
                                        ],
                                        'yAxis' => [
                                            'title' => ['text' => 'จำนวน(บาท)'],
                                            
                                        ],
                                        'plotOptions' => [
                                            'series' => [
                                                'borderWidth' => '1',
                                                'dataLabels' => [
                                                    'enabled' => true,
                                                ],
                                            ],
                                            'column'=>[
                                                'borderWidth' => '1',
                                                'dataLabels' => [
                                                    'enabled' => true,
                                                ],
                                            ],
                                        ],
                                        'series' =>$pieyearbudget
                                    ]
                                ]);
                        ?>
                    </div>
                </div>
            </div>
    </div>
<hr>
<div class="row">
        <div class="col-lg-12 ">  
                <div class="card">
                    <div class="card-header bg-gradient-info">
                    <h5><i class="far fa-chart-bar"> ข้อมูลบทความวารสาร</i></h5>
                    
                    </div>
                    <div class="card-body">
                        <?php
                        
                                echo Highcharts::widget([
                                    'options' => [
                                        'chart'=>[
                                            'type'=>'column',
                                            'colorByPoint' => true,
                                        // 'height'=> 400,
                                        ],
                                        'title' => ['text' => ''],
                                        'xAxis' => [
                                            'categories' => $categoriesResyear,
                                        ],
                                        'yAxis' => [
                                            'title' => ['text' => 'จำนวน']
                                        ],
                                        'plotOptions' => [
                                            'series' => [
                                                'borderWidth' => '0',
                                                'dataLabels' => [
                                                    'enabled' => true,
                                                ]
                                            ]
                                        ],
                                        
                                        'series' =>$seriesPublication, 

                                    ]
                                ]);
                            ?>
                    </div>
                    
                </div>
        </div>
</div>
<hr>
</div>
