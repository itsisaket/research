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
$prefix   = $model->hasprefix->prefixname ?? '';
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
    $count = (int)$count;
    return Html::a(
        "<div class='card border-0 shadow-sm h-100 text-white {$bg}'>
            <div class='card-body d-flex justify-content-between align-items-center'>
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
        ['class' => 'text-decoration-none d-block', 'encode' => false, 'data-pjax' => 0]
    );
};

/**
 * Card แสดง "รายการล่าสุด" ของแต่ละโมดูล
 * @param string $title
 * @param string $icon
 * @param array $items ActiveRecord[]
 * @param array $indexUrl
 * @param array $viewRoute ['route/view', 'idField' => 'pk'] -> ส่งแค่ route แล้วใช้ primaryKey อัตโนมัติ
 * @param array $titleFields ฟิลด์ที่เป็นชื่อเรื่อง (วนหา)
 */
$recentCard = function($title, $icon, $items, $indexUrl, $viewRoute, $titleFields = []) {

    // หา PK จาก class (ถ้าไม่มี items → ใช้ id)
    $pk = 'id';
    if (!empty($items)) {
        $cls = get_class($items[0]);
        $pk  = $cls::primaryKey()[0] ?? 'id';
    }

    $head = "
      <div class='card-header bg-white d-flex justify-content-between align-items-center'>
        <strong><i class='{$icon}'></i> {$title}</strong>
        ".Html::a('<i class="bi bi-list-ul"></i> ดูทั้งหมด', $indexUrl, [
            'class' => 'btn btn-sm btn-outline-primary',
            'encode' => false,
            'data-pjax' => 0,
        ])."
      </div>
    ";

    if (empty($items)) {
        $body = "<div class='p-3 text-muted small'>ยังไม่มีข้อมูล</div>";
    } else {
        $body = "<ul class='list-group list-group-flush'>";
        foreach ($items as $m) {
            $id = $m->$pk ?? null;

            // เลือกชื่อเรื่องจากฟิลด์ที่มีจริงก่อน
            $t = null;
            foreach ($titleFields as $f) {
                if (isset($m->$f) && trim((string)$m->$f) !== '') { $t = (string)$m->$f; break; }
            }
            if ($t === null) $t = $m->title ?? $m->project_name ?? $m->topic ?? null;
            if ($t === null) $t = '#'.(string)$id;

            $label = "<span class='text-truncate d-inline-block' style='max-width:92%;'>".Html::encode($t)."</span>";

            $link = $id
                ? Html::a($label, [$viewRoute, 'id' => $id], [
                    'class' => 'text-decoration-none',
                    'encode' => false,
                    'data-pjax' => 0,
                ])
                : $label;

            $body .= "
              <li class='list-group-item py-2 d-flex justify-content-between align-items-center'>
                {$link}
                <span class='text-muted'><i class='bi bi-box-arrow-up-right'></i></span>
              </li>
            ";
        }
        $body .= "</ul>";
    }

    return "<div class='card border-0 shadow-sm h-100'>{$head}<div class='card-body p-0'>{$body}</div></div>";
};

?>

<div class="account-view container-fluid">

  <!-- ===== Summary ===== -->
  <div class="row">
    <div class="col-md-6 col-lg-3 mb-3">
      <?= $statCard('งานวิจัย', $cntResearch ?? 0, 'bi bi-journal-text',
          ['researchpro/index', 'ResearchproSearch[uid]' => $model->uid], 'bg-primary') ?>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
      <?= $statCard('บทความ', $cntArticle ?? 0, 'bi bi-file-earmark-text',
          ['article/index', 'ArticleSearch[uid]' => $model->uid], 'bg-danger') ?>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
      <?= $statCard('การนำไปใช้', $cntUtil ?? 0, 'bi bi-lightbulb',
          ['utilization/index', 'UtilizationSearch[uid]' => $model->uid], 'bg-warning') ?>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
      <?= $statCard('บริการวิชาการ', $cntService ?? 0, 'bi bi-people',
          ['academic-service/index', 'AcademicServiceSearch[uid]' => $model->uid], 'bg-success') ?>
    </div>
  </div>

  <!-- ===== Profile Detail ===== -->
  <div class="row">
    <div class="col-12 mb-3">
      <div class="card border-0 shadow-sm">
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

  <!-- ===== รายการข้อมูล (ต่อท้าย แยกตาม Card) ===== -->
  <div class="row mt-2">
    <div class="col-12 col-md-6 mb-3">
      <?= $recentCard(
          'งานวิจัย',
          'bi bi-journal-text',
          $researchLatest ?? [],
          ['researchpro/index', 'ResearchproSearch[uid]' => $model->uid],
          'researchpro/view',
          ['project_name', 'research_title', 'title']
      ) ?>
    </div>

    <div class="col-12 col-md-6 mb-3">
      <?= $recentCard(
          'บทความ',
          'bi bi-file-earmark-text',
          $articleLatest ?? [],
          ['article/index', 'ArticleSearch[uid]' => $model->uid],
          'article/view',
          ['title', 'article_title', 'topic']
      ) ?>
    </div>

    <div class="col-12 col-md-6 mb-3">
      <?= $recentCard(
          'การนำไปใช้',
          'bi bi-lightbulb',
          $utilLatest ?? [],
          ['utilization/index', 'UtilizationSearch[uid]' => $model->uid],
          'utilization/view',
          ['util_title', 'title', 'topic']
      ) ?>
    </div>

    <div class="col-12 col-md-6 mb-3">
      <?= $recentCard(
          'บริการวิชาการ',
          'bi bi-people',
          $serviceLatest ?? [],
          ['academic-service/index', 'AcademicServiceSearch[uid]' => $model->uid],
          'academic-service/view',
          ['title', 'service_title', 'topic']
      ) ?>
    </div>
  </div>

</div>
