<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

use kartik\widgets\Select2;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\DepDrop;
use kartik\widgets\FileInput;
use kartik\date\DatePicker;

use miloschuman\highcharts\Highcharts;
use yii\web\JsExpression;

use function PHPSTORM_META\type;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use miloschuman\highcharts\HighchartsAsset;
HighchartsAsset::register($this)->withScripts(['modules/exporting']);

/** @var yii\web\View $this */

$test=[['name'=>'123','data'=>[11,21,31]],['name'=>'567','data'=>[18,8,6]]];
$this->title = 'Dashboard';
$u = Yii::$app->user->identity ?? null;
?>
<h1>Welcome</h1>
<?php if ($u): ?>
  <p>สวัสดี, <strong><?= Html::encode($u->name) ?></strong> (<?= Html::encode($u->username) ?>)</p>
  <form method="post" action="/site/logout"><button class="btn btn-danger">Logout</button></form>
<?php else: ?>
  <p>ยังไม่ได้ล็อกอิน</p>
<?php endif; ?>

<div class="site-index">

  <!-- Page header (Berry style) -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center gap-3">
        <?= Html::img('@web/img/'.$model->org_id.'.png', ['style'=>'height:64px; width:auto;']) ?>
        <div>
          <h1 class="h3 mb-1 text-primary"><?= Html::encode($model->org_name) ?></h1>
          <div class="text-muted">ระบบสารสนเทศงานวิจัย เพื่อการบริหารจัดการ</div>
        </div>
      </div>

      <!-- Filter bar -->
      <form class="row g-2 align-items-center mt-3" action="site/report1">
        <div class="col-auto">
          <label class="col-form-label">ปีงบประมาณ</label>
        </div>
        <div class="col-auto">
          <div class="input-group">
            <select class="form-select" name="resyear">
              <?php foreach ($categoriesResyear as $Org): ?>
                <option><?= Html::encode($Org); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-warning" type="submit">Go</button>
          </div>
        </div>
      </form>
    </div>
  </div>

<!-- KPI cards (Berry style: gradient + big watermark icon + centered text) -->
<div class="row g-3">

  <!-- 1) โครงการวิจัย/บริการวิชาการ -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card text-white border-0 shadow-lg h-100 position-relative overflow-hidden"
         style="
          border-radius:16px; min-height:130px;
          background:
            radial-gradient(120px 120px at 110% -10%, rgba(255,255,255,.22), rgba(255,255,255,0) 60%),
            radial-gradient(200px 200px at 120% 80%, rgba(255,255,255,.12), rgba(255,255,255,0) 70%),
            linear-gradient(135deg,#7e22ce,#2563eb);
         ">
      <!-- watermark icon -->
      <i class="ti ti-layout-grid position-absolute"
         style="left:16px; top:50%; transform:translateY(-50%); font-size:96px; opacity:.15;"></i>

      <!-- content -->
      <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-4">
        <div class="fw-semibold">โครงการวิจัย/บริการวิชาการ</div>
        <div class="fs-2 fw-bold lh-1"><?= number_format((int)$countProject); ?></div>
        <div class="small opacity-75">รายการ</div>
      </div>
    </div>
  </div>

  <!-- 2) บทความวารสาร -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card text-white border-0 shadow-lg h-100 position-relative overflow-hidden"
         style="
          border-radius:16px; min-height:130px;
          background:
            radial-gradient(120px 120px at 110% -10%, rgba(255,255,255,.18), rgba(255,255,255,0) 60%),
            radial-gradient(200px 200px at 120% 80%, rgba(255,255,255,.10), rgba(255,255,255,0) 70%),
            linear-gradient(135deg,#1e3a8a,#0ea5e9);
         ">
      <i class="ti ti-compass position-absolute"
         style="left:16px; top:50%; transform:translateY(-50%); font-size:96px; opacity:.15;"></i>

      <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-4">
        <div class="fw-semibold">บทความวารสาร</div>
        <div class="fs-2 fw-bold lh-1"><?= number_format((int)$countArticle); ?></div>
        <div class="small opacity-75">รายการ</div>
      </div>
    </div>
  </div>

  <!-- 3) นักวิจัย -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card text-white border-0 shadow-lg h-100 position-relative overflow-hidden"
         style="
          border-radius:16px; min-height:130px;
          background:
            radial-gradient(120px 120px at 110% -10%, rgba(255,255,255,.22), rgba(255,255,255,0) 60%),
            radial-gradient(200px 200px at 120% 80%, rgba(255,255,255,.12), rgba(255,255,255,0) 70%),
            linear-gradient(135deg,#ec4899,#a21caf);
         ">
      <i class="ti ti-user position-absolute"
         style="left:16px; top:50%; transform:translateY(-50%); font-size:96px; opacity:.15;"></i>

      <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-4">
        <div class="fw-semibold">นักวิจัย</div>
        <div class="fs-2 fw-bold lh-1"><?= number_format((int)$countuser); ?></div>
        <div class="small opacity-75">คน</div>
      </div>
    </div>
  </div>

  <!-- 4) นำไปใช้ประโยชน์ -->
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card text-white border-0 shadow-lg h-100 position-relative overflow-hidden"
         style="
          border-radius:16px; min-height:130px;
          background:
            radial-gradient(120px 120px at 110% -10%, rgba(255,255,255,.22), rgba(255,255,255,0) 60%),
            radial-gradient(200px 200px at 120% 80%, rgba(255,255,255,.12), rgba(255,255,255,0) 70%),
            linear-gradient(135deg,#10b981,#059669);
         ">
      <i class="ti ti-flag position-absolute"
         style="left:16px; top:50%; transform:translateY(-50%); font-size:96px; opacity:.15;"></i>

      <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-4">
        <div class="fw-semibold">นำไปใช้ประโยชน์</div>
        <div class="fs-2 fw-bold lh-1"><?= number_format((int)$countUtilization); ?></div>
        <div class="small opacity-75">รายการ</div>
      </div>
    </div>
  </div>

</div>


  <?php 
  $session = Yii::$app->session;
  if (($session['ty'] ?? null) == 11): ?>
    <div class="card shadow-sm mt-4">
      <div class="card-header bg-primary text-white">
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-chart-bar"></i>
          <span>ภาพรวมแยกตามหน่วยงาน</span>
        </div>
      </div>
      <div class="card-body">
        <?php
          echo Highcharts::widget([
            'options' => [
              'chart'=>[
                'type'=>'column',
                'colorByPoint' => true
              ],
              'title' => ['text' => ''],
              'xAxis' => [
                'categories' => $categoriesOrganize,
                'labels'=>['rotation'=> 0],
              ],
              'yAxis' => [
                'title' => ['text' => 'จำนวน']
              ],
              'plotOptions' => [
                'series' => [
                  'borderWidth' => 0,
                  'dataLabels' => ['enabled' => true],
                ],
                'column'=>[
                  'borderWidth' => 0,
                  'dataLabels' => ['enabled' => true],
                ],
              ],
              'series' => $seriesOrganize,
            ]
          ]);
        ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3 mt-1">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-chart-line"></i><span>ข้อมูลรายปี</span>
          </div>
        </div>
        <div class="card-body">
          <?php
            echo Highcharts::widget([
              'options' => [
                'chart'=>['type'=>'spline'],
                'title' => ['text' => ''],
                'xAxis' => ['categories' => $categoriesResyear],
                'yAxis' => ['title' => ['text' => 'จำนวน']],
                'plotOptions' => [
                  'series' => [
                    'borderWidth' => 0,
                    'dataLabels' => ['enabled' => true],
                  ],
                ],
                'series' => $seriesResyear,
              ]
            ]);
          ?>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-warning">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-chart-bar"></i><span>ข้อมูลแหล่งทุน</span>
          </div>
        </div>
        <div class="card-body">
          <?php
            echo Highcharts::widget([
              'options' => [
                'chart'=>[
                  'type'=>'bar',
                  'colorByPoint' => true
                ],
                'xAxis' => [
                  'categories' => $categoriesResGency,
                  'labels'=>['rotation'=> 0],
                ],
                'yAxis' => ['title' => ['text' => 'จำนวน']],
                'title' => ['text' => ''],
                'plotOptions' => [
                  'series' => [
                    'borderWidth' => 0,
                    'dataLabels' => ['enabled' => true],
                  ],
                ],
                'series' => [[
                  'name'=>'โครงการวิจัย',
                  'data'=>$pieResGency,
                ]]
              ]
            ]);
          ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mt-4">
    <div class="card-header bg-warning">
      <div class="d-flex align-items-center gap-2">
        <i class="ti ti-currency-baht"></i>
        <span>ข้อมูลงบประมาณ (แหล่งทุน)</span>
      </div>
    </div>
    <div class="card-body">
      <?php
        echo Highcharts::widget([
          'options' => [
            'chart'=>[
              'type'=>'spline',
              'height'=> 600
            ],
            'title' => ['text' => ''],
            'xAxis' => [
              'categories' => $categoriesResGency,
              'labels'=>['rotation'=> 0],
            ],
            'yAxis' => ['title' => ['text' => 'จำนวน(บาท)']],
            'plotOptions' => [
              'series' => [
                'borderWidth' => 1,
                'dataLabels' => ['enabled' => true],
              ],
            ],
            'series' => $pieyearbudget
          ]
        ]);
      ?>
    </div>
  </div>

  <div class="card shadow-sm mt-4">
    <div class="card-header bg-primary text-white">
      <div class="d-flex align-items-center gap-2">
        <i class="ti ti-books"></i><span>ข้อมูลบทความวารสาร</span>
      </div>
    </div>
    <div class="card-body">
      <?php
        echo Highcharts::widget([
          'options' => [
            'chart'=>['type'=>'column'],
            'title' => ['text' => ''],
            'xAxis' => ['categories' => $categoriesResyear],
            'yAxis' => ['title' => ['text' => 'จำนวน']],
            'plotOptions' => [
              'series' => [
                'borderWidth' => 0,
                'dataLabels' => ['enabled' => true],
              ],
            ],
            'series' => $seriesPublication,
          ]
        ]);
      ?>
    </div>
  </div>

</div>
