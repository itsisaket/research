<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Account */
/* @var $username string|null */
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

// ===== helpers =====
$prefixName = $model->hasprefix->prefixname ?? '';
$fullName = trim(($prefixName ? $prefixName.' ' : '').($model->uname ?? '').' '.($model->luname ?? ''));
$fullName = $fullName !== '' ? $fullName : '-';

$posName = $model->hasposition->positionname ?? '-';
$orgName = $model->hasorg->org_name ?? '-';

$badge = function($text){
    $text = (string)$text;
    $class = 'badge-secondary';
    $t = mb_strtolower($text);
    if (strpos($t, 'admin') !== false || strpos($t, 'ผู้ดูแล') !== false) $class = 'badge-danger';
    elseif (strpos($t, 'user') !== false || strpos($t, 'ผู้ใช้งาน') !== false) $class = 'badge-primary';
    elseif (strpos($t, 'review') !== false || strpos($t, 'ผู้ตรวจ') !== false) $class = 'badge-warning';
    return '<span class="badge '.$class.'">'.Html::encode($text ?: '-').'</span>';
};

$statCard = function($title, $count, $icon, $url, $bgClass='bg-primary'){
    $count = (int)$count;
    return Html::a(
        "<div class='card border-0 shadow-sm h-100 text-white {$bgClass}'>
            <div class='card-body d-flex align-items-center justify-content-between'>
              <div>
                <div class='small text-white-50 mb-1'>{$title}</div>
                <div class='h3 mb-0 font-weight-bold'>{$count}</div>
                <div class='small text-white-50'>คลิกเพื่อดูรายการ</div>
              </div>
              <div class='text-white-50' style='font-size:34px; line-height:1'>
                <i class='{$icon}'></i>
              </div>
            </div>
        </div>",
        $url,
        ['class' => 'text-decoration-none d-block', 'data-pjax' => 0, 'encode' => false]
    );
};

$recentCard = function($title, $icon, $items, $indexUrl, $viewRoute, $pkField, $titleFields = []){
    $header = "
      <div class='card-header bg-white d-flex align-items-center justify-content-between'>
        <div class='d-flex align-items-center'>
          <i class='{$icon} mr-2'></i>
          <strong>{$title}</strong>
        </div>
        ".Html::a('<i class="bi bi-list-ul"></i> ดูทั้งหมด', $indexUrl, [
            'class' => 'btn btn-sm btn-outline-primary',
            'data-pjax' => 0,
            'encode' => false,
        ])."
      </div>
    ";

    $body = "";
    if (empty($items)) {
        $body = "<div class='p-3 text-muted small'>ยังไม่มีข้อมูล</div>";
    } else {
        $body .= "<ul class='list-group list-group-flush'>";
        foreach ($items as $m) {
            $id = $m->$pkField ?? null;

            $t = null;
            foreach ($titleFields as $f) {
                if (isset($m->$f) && trim((string)$m->$f) !== '') { $t = (string)$m->$f; break; }
            }
            if ($t === null) $t = $m->title ?? $m->project_name ?? $m->topic ?? null;
            if ($t === null) $t = '#'.(string)$id;

            $titleText = "<span class='d-inline-block text-truncate' style='max-width: 92%;'>".Html::encode($t)."</span>";

            $link = $id
                ? Html::a($titleText, [$viewRoute, 'id' => $id], [
                    'class' => 'text-decoration-none',
                    'data-pjax' => 0,
                    'encode' => false
                ])
                : $titleText;

            $body .= "
              <li class='list-group-item py-2 d-flex justify-content-between align-items-center'>
                {$link}
                <span class='text-muted'><i class='bi bi-box-arrow-up-right'></i></span>
              </li>
            ";
        }
        $body .= "</ul>";
    }

    return "<div class='card border-0 shadow-sm h-100'>{$header}<div class='card-body p-0'>{$body}</div></div>";
};

?>

<div class="account-view container-fluid">

  <!-- Header -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex justify-content-between align-items-start flex-wrap">
      <div>
        <div class="text-muted small mb-1"><i class="bi bi-person-circle"></i> โปรไฟล์ผู้ใช้</div>
        <h4 class="mb-1 text-primary"><?= Html::encode($fullName) ?></h4>
        <div class="text-muted">
          <span class="mr-2"><i class="bi bi-at"></i> <?= Html::encode($username ?? '-') ?></span>
          <span class="mr-2">·</span>
          <span><?= $badge($posName) ?></span>
        </div>
        <div class="text-muted small mt-1">
          <i class="bi bi-building"></i> <?= Html::encode($orgName) ?>
        </div>
      </div>

      <div class="btn-group mt-2 mt-md-0" role="group" aria-label="actions">
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

  <!-- Summary Stat (ครบ 4 กล่อง + จัดให้บาลานซ์) -->
  <div class="row">
    <div class="col-12 col-md-6 col-lg-3 mb-3">
      <?= $statCard('งานวิจัย', $cntResearch ?? 0, 'bi bi-journal-text',
        ['/researchpro/index', 'ResearchproSearch[username]' => $username], 'bg-primary') ?>
    </div>

    <div class="col-12 col-md-6 col-lg-3 mb-3">
      <?= $statCard('บทความ', $cntArticle ?? 0, 'bi bi-file-earmark-text',
        ['/article/index', 'ArticleSearch[username]' => $username], 'bg-danger') ?>
    </div>

    <div class="col-12 col-md-6 col-lg-3 mb-3">
      <?= $statCard('การนำไปใช้ประโยชน์', $cntUtil ?? 0, 'bi bi-lightbulb',
        ['/utilization/index', 'UtilizationSearch[username]' => $username], 'bg-warning') ?>
    </div>

    <div class="col-12 col-md-6 col-lg-3 mb-3">
      <?= $statCard('บริการวิชาการ', $cntService ?? 0, 'bi bi-people',
        ['/academic-service/index', 'AcademicServiceSearch[username]' => $username], 'bg-success') ?>
    </div>
  </div>

  <!-- Profile + Recent -->
  <div class="row">
    <!-- Profile detail -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white">
          <strong><i class="bi bi-card-list"></i> ข้อมูลผู้ใช้</strong>
        </div>
        <div class="card-body">
          <?= DetailView::widget([
              'model' => $model,
              'options' => ['class' => 'table table-sm table-striped mb-0'],
              'attributes' => [
                  ['label' => 'ชื่อ - สกุล', 'value' => $fullName],
                  ['label' => 'Username', 'value' => $username ?? '-'],
                  ['label' => 'อีเมล์', 'value' => $model->email ?? '-'],
                  ['label' => 'เบอร์ติดต่อ', 'value' => $model->tel ?? '-'],
                  ['label' => 'สังกัด', 'value' => $orgName],
                  ['label' => 'สถานะ', 'format' => 'raw', 'value' => $badge($posName)],
              ],
          ]) ?>
        </div>
      </div>
    </div>

    <!-- Recent lists (จัด 2x2) -->
    <div class="col-12 col-lg-8 mb-3">
      <div class="row">
        <div class="col-12 col-md-6 mb-3">
          <?= $recentCard(
              'งานวิจัยล่าสุด',
              'bi bi-journal-text',
              $researchLatest ?? [],
              ['/researchpro/index', 'ResearchproSearch[username]' => $username],
              'researchpro/view',
              \app\models\Researchpro::primaryKey()[0] ?? 'id',
              ['project_name', 'research_title', 'title']
          ) ?>
        </div>

        <div class="col-12 col-md-6 mb-3">
          <?= $recentCard(
              'บทความล่าสุด',
              'bi bi-file-earmark-text',
              $articleLatest ?? [],
              ['/article/index', 'ArticleSearch[username]' => $username],
              'article/view',
              \app\models\Article::primaryKey()[0] ?? 'id',
              ['title', 'article_title']
          ) ?>
        </div>

        <div class="col-12 col-md-6 mb-3">
          <?= $recentCard(
              'การนำไปใช้ประโยชน์ล่าสุด',
              'bi bi-lightbulb',
              $utilLatest ?? [],
              ['/utilization/index', 'UtilizationSearch[username]' => $username],
              'utilization/view',
              \app\models\Utilization::primaryKey()[0] ?? 'id',
              ['util_title', 'title', 'topic']
          ) ?>
        </div>

        <div class="col-12 col-md-6 mb-3">
          <?= $recentCard(
              'บริการวิชาการล่าสุด',
              'bi bi-people',
              $serviceLatest ?? [],
              ['/academic-service/index', 'AcademicServiceSearch[username]' => $username],
              'academic-service/view',
              \app\models\AcademicService::primaryKey()[0] ?? 'id',
              ['title', 'service_title', 'topic']
          ) ?>
        </div>
      </div>
    </div>
  </div>

</div>
