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


</div>
