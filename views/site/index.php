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

?>


<div class="site-index">

  <!-- Page header (Berry style) -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center gap-3">
        <?php // Html::img('@web/img/'.$model->org_id.'.png', ['style'=>'height:64px; width:auto;']) ?>
        <div>
          <h1 class="h3 mb-1 text-primary"><?php // Html::encode($model->org_name) ?></h1>
          <div class="text-muted">ระบบสารสนเทศงานวิจัย เพื่อการบริหารจัดการ</div>
        </div>
      </div>
    </div>
  </div>


</div>
