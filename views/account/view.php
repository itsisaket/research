<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Account */
/* @var $cntResearch int */
/* @var $cntArticle int */
/* @var $cntUtil int */
/* @var $cntService int */
/* @var $researchLatest array */
/* @var $articleLatest array */
/* @var $utilLatest array */
/* @var $serviceLatest array */

$this->title = 'โปรไฟล์ผู้ใช้';
$this->params['breadcrumbs'][] = ['label' => 'Accounts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/* ===== helpers ===== */
$prefix = $model->hasprefix->prefixname ?? '';
$fullName = trim(($prefix ? $prefix.' ' : '').($model->uname ?? '').' '.($model->luname ?? ''));
$fullName = $fullName ?: '-';

$orgName = $model->hasorg->org_name ?? '-';
$posName = $model->hasposition->positionname ?? '-';

$badge = function($text){
    $t = mb_strtolower((string)$text);
    $class = 'badge-secondary';
    if (strpos($t, 'admin') !== false || strpos($t, 'ผู้ดูแล') !== false) $class = 'badge-danger';
    elseif (strpos($t, 'user') !== false || strpos($t, 'ผู้ใช้งาน') !== false) $class = 'badge-primary';
    elseif (strpos($t, 'review') !== false || strpos($t, 'ผู้ตรวจ') !== false) $class = 'badge-warning';
    return '<span class="badge '.$class.'">'.Html::encode($text ?: '-').'</span>';
};

$statCard = function($title, $count, $icon, $url, $bg){
    return Html::a(
        "<div class='card border-0 shadow-sm h-100 text-white {$bg}'>
            <div class='card-body d-flex justify-content-between align-items-center'>
                <div>
                    <div class='small text-white-50'>{$title}</div>
                    <div class='h3 mb-0 font-weight-bold'>{$count}</div>
                </div>
                <div style='font-size:34px'><i class='{$icon}'></i></div>
            </div>
        </div>",
        $url,
        ['class' => 'text-decoration-none d-block', 'encode' => false]
    );
};

?>

<div class="account-view container-fluid">

<!-- ===== Header ===== -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body d-flex justify-content-between flex-wrap">
    <div>
      <div class="text-muted small">โปรไฟล์ผู้ใช้</div>
      <h4 class="text-primary mb-1"><?= Html::encode($fullName) ?></h4>
      <div class="text-muted">
        <?= $badge($posName) ?> · <?= Html::encode($orgName) ?>
      </div>
    </div>

    <div class="btn-group mt-2">
      <?= Html::a('<i class="bi bi-arrow-left"></i> ย้อนกลับ', ['index'], [
          'class' => 'btn btn-outline-secondary',
          'encode' => false
      ]) ?>
      <?= Html::a('<i class="bi bi-pencil-square"></i> แก้ไข', ['update', 'id' => $model->uid], [
          'class' => 'btn btn-primary',
          'encode' => false
      ]) ?>
    </div>
  </div>
</div>

<!-- ===== Summary ===== -->
<div class="row">
  <div class="col-md-6 col-lg-3 mb-3">
    <?= $statCard('งานวิจัย', $cntResearch, 'bi bi-journal-text',
        ['researchpro/index', 'ResearchproSearch[uid]' => $model->uid], 'bg-primary') ?>
  </div>
  <div class="col-md-6 col-lg-3 mb-3">
    <?= $statCard('บทความ', $cntArticle, 'bi bi-file-earmark-text',
        ['article/index', 'ArticleSearch[uid]' => $model->uid], 'bg-danger') ?>
  </div>
  <div class="col-md-6 col-lg-3 mb-3">
    <?= $statCard('การนำไปใช้', $cntUtil, 'bi bi-lightbulb',
        ['utilization/index', 'UtilizationSearch[uid]' => $model->uid], 'bg-warning') ?>
  </div>
  <div class="col-md-6 col-lg-3 mb-3">
    <?= $statCard('บริการวิชาการ', $cntService, 'bi bi-people',
        ['academic-service/index', 'AcademicServiceSearch[uid]' => $model->uid], 'bg-success') ?>
  </div>
</div>

<!-- ===== Profile Detail ===== -->
<div class="row">
  <div class="col-lg-5 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white">
        <strong><i class="bi bi-person-badge"></i> ข้อมูลผู้ใช้</strong>
      </div>
      <div class="card-body">
        <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-sm table-striped mb-0'],
          'attributes' => [
            ['label' => 'ชื่อ - สกุล', 'value' => $fullName],
            ['label' => 'อีเมล์', 'value' => $model->email ?? '-'],
            ['label' => 'เบอร์โทร', 'value' => $model->tel ?? '-'],
            ['label' => 'สังกัด', 'value' => $orgName],
            ['label' => 'สถานะ', 'format' => 'raw', 'value' => $badge($posName)],
          ],
        ]) ?>
      </div>
    </div>
  </div>
</div>

</div>
