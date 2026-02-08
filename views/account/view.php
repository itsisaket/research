<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Account */
/* @var $cntResearch int */
/* @var $cntArticle int */
/* @var $cntUtil int */
/* @var $cntService int */
/* @var $researchLatest app\models\Researchpro[] */
/* @var $articleLatest app\models\Article[] */
/* @var $utilLatest app\models\Utilization[] */
/* @var $serviceLatest app\models\AcademicService[] */

$this->title = 'โปรไฟล์ผู้ใช้';
$this->params['breadcrumbs'][] = ['label' => 'Accounts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/** ===== Helpers (PHP 7.4 safe) ===== */
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;

$isAdmin = function () use ($me) {
    return ($me instanceof \app\models\Account) && in_array((int)($me->position ?? 0), [1, 4], true);
};

$safeRel = function ($obj, $relName) {
    if (!is_object($obj)) return null;
    try {
        return $obj->$relName ?? null;
    } catch (\Throwable $e) {
        return null;
    }
};

$getFirstNonEmpty = function ($obj, array $fields, $fallback = '-') {
    if (!is_object($obj)) return $fallback;
    foreach ($fields as $f) {
        if (isset($obj->$f) && trim((string)$obj->$f) !== '') {
            return (string)$obj->$f;
        }
    }
    return $fallback;
};

$fullName = trim((string)($model->uname ?? '') . ' ' . (string)($model->luname ?? ''));
if ($fullName === '') $fullName = (string)($model->username ?? '-');

$initials = function ($name) {
    $name = trim((string)$name);
    if ($name === '') return '?';
    $chars = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY);
    $a = $chars[0] ?? '?';
    $b = $chars[1] ?? '';
    return mb_strtoupper($a . $b, 'UTF-8');
};

$orgObj = $safeRel($model, 'hasorg');
$posObj = $safeRel($model, 'hasposition');
$orgName = (is_object($orgObj) && isset($orgObj->org_name)) ? $orgObj->org_name : '-';
$posName = (is_object($posObj) && isset($posObj->positionname)) ? $posObj->positionname : '-';

// ✅ Account PK ของคุณคือ uid
$accountPkVal = $model->uid ?? null;

/**
 * KPI Card (Bootstrap utilities + inline gradient only)
 */
$kpiCard = function (
    $title,
    $value,
    $icon,
    $g1,
    $g2,
    $iconBg = 'rgba(255,255,255,.22)',
    $textColor = '#fff'
) {
    $style = "background: linear-gradient(135deg, {$g1}, {$g2}); color: {$textColor};";
    return "
    <div class='card border-0 shadow-sm rounded-4 overflow-hidden' style='{$style}'>
      <div class='card-body p-4'>
        <div class='d-flex align-items-start justify-content-between'>
          <div>
            <div class='fw-semibold' style='opacity:.9'>{$title}</div>
            <div class='display-6 fw-bold mb-0'>{$value}</div>
          </div>
          <div class='rounded-4 p-3' style='background: {$iconBg};'>
            <i class='{$icon} fs-3'></i>
          </div>
        </div>
      </div>
    </div>";
};

/**
 * Card List Builder (สีสันตามภาพตัวอย่าง)
 * - ไม่เพิ่มไฟล์ CSS ใหม่
 * - ใช้ Bootstrap + inline gradient เฉพาะที่จำเป็น
 * - ชื่อแสดงเต็ม ไม่จำกัดบรรทัด
 * - รองรับ pk หลายชื่อ (fallback)
 * - ส่ง param name ให้ตรงกับ controller จริง
 */
$listCard = function (
    $title,
    $icon,
    $items,
    $viewRoute,
    array $pkFields,
    $paramName,
    array $titleFields,
    $indexRoute = null,
    array $indexParams = [],
    $g1 = '#6f42c1',
    $g2 = '#0d6efd'
) use ($getFirstNonEmpty) {

    $headStyle = "background: linear-gradient(90deg, {$g1}, {$g2});";
    $headerRight = "<span class='text-white-50 small'>ล่าสุด 10 รายการ</span>";

    if ($indexRoute) {
        $headerRight = Html::a(
            "<i class='bi bi-list-ul'></i> ดูทั้งหมด",
            array_merge([$indexRoute], $indexParams),
            [
                'class' => 'btn btn-sm btn-light bg-white bg-opacity-25 border-0 text-white',
                'encode' => false,
                'data-pjax' => 0
            ]
        );
    }

    $html = "<div class='card border-0 shadow-sm rounded-4 overflow-hidden'>
        <div class='card-header border-0 py-3' style='{$headStyle}'>
          <div class='d-flex justify-content-between align-items-center'>
            <div class='fw-semibold text-white'><i class='{$icon}'></i> {$title}</div>
            <div>{$headerRight}</div>
          </div>
        </div>
        <div class='card-body p-0'>";

    if (empty($items)) {
        $html .= "<div class='p-3 text-muted small'>ไม่มีข้อมูล</div>";
    } else {
        $html .= "<div class='list-group list-group-flush'>";

        foreach ($items as $m) {
            $id = null;
            if (is_object($m)) {
                foreach ($pkFields as $pk) {
                    if (isset($m->$pk) && $m->$pk !== null && $m->$pk !== '') {
                        $id = $m->$pk;
                        break;
                    }
                }
            }

            $name = $getFirstNonEmpty($m, $titleFields, '-');

            // ชื่อเต็ม ไม่จำกัดบรรทัด
            $label = "<span class='d-block' style='white-space:normal; overflow:visible; text-overflow:clip; line-height:1.45; word-break:break-word;'>"
                . Html::encode($name) .
                "</span>";

            if (!$id) {
                $html .= "<div class='list-group-item py-2 text-muted'>{$label}</div>";
                continue;
            }

            $url = [$viewRoute, $paramName => $id];

            $html .= Html::a(
                "<div class='d-flex gap-2 align-items-start'>
                    <div class='flex-grow-1'>{$label}</div>
                    <div class='text-muted flex-shrink-0 pt-1'><i class='bi bi-box-arrow-up-right'></i></div>
                 </div>",
                $url,
                [
                    'class' => 'list-group-item list-group-item-action py-2',
                    'encode' => false,
                    'data-pjax' => 0,
                    'title' => $name,
                ]
            );
        }

        $html .= "</div>";
    }

    $html .= "</div></div>";
    return $html;
};
?>

<div class="container-fluid py-2">
  <!-- ===== Header Profile (ใช้ bootstrap + สีอ่อน) ===== -->
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">
      <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">

        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow"
               style="width:56px;height:56px;background: linear-gradient(135deg,#7B2FF7,#0A8BCB);">
            <span class="fw-bold"><?= Html::encode($initials($fullName)) ?></span>
          </div>

          <div>
            <div class="h5 mb-0"><?= Html::encode($fullName) ?></div>
            <div class="text-muted small">
              <i class="bi bi-diagram-3"></i> <?= Html::encode($orgName) ?>
              <span class="mx-2">•</span>
              <i class="bi bi-award"></i> <?= Html::encode($posName) ?>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <?= Html::a('<i class="bi bi-arrow-left"></i> กลับ', ['index'], [
              'class' => 'btn btn-outline-secondary',
              'encode' => false,
          ]) ?>

          <?php if ($isAdmin() && $accountPkVal !== null): ?>
            <?= Html::a('<i class="bi bi-pencil-square"></i> แก้ไข', ['update', 'id' => $accountPkVal], [
                'class' => 'btn btn-primary',
                'encode' => false,
                'data-pjax' => 0,
            ]) ?>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
  <!-- ===== Top KPI Row (สีตามภาพตัวอย่าง) ===== -->
  <div class="row g-3 mb-3">

    <div class="col-12 col-md-3">
      <?= $kpiCard('นักวิจัยทั้งหมด', (int)$cntResearch, 'bi bi-people-fill', '#7B2FF7', '#9B40FF') ?>
    </div>

    <div class="col-12 col-md-3">
      <?= $kpiCard('โครงการวิจัย', (int)$cntResearch, 'bi bi-flask', '#0A8BCB', '#0067B8') ?>
    </div>

    <div class="col-12 col-md-3">
      <?= $kpiCard('บทความวิจัย', (int)$cntArticle, 'bi bi-file-earmark-text', '#F04646', '#C81D1D') ?>
    </div>

    <div class="col-12 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <div class="fw-semibold text-muted">บริการวิชาการ</div>
              <div class="display-6 fw-bold mb-0 text-primary"><?= (int)$cntService ?></div>
            </div>
            <div class="rounded-4 p-3 bg-success-subtle">
              <i class="bi bi-arrow-repeat fs-3 text-success"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>


  <!-- ===== User Detail ===== -->
  <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
    <div class="card-header border-0 text-white"
         style="background: linear-gradient(90deg,#7B2FF7,#0A8BCB);">
      <div class="fw-semibold"><i class="bi bi-info-circle"></i> ข้อมูลผู้ใช้</div>
    </div>
    <div class="card-body">
      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-sm table-striped mb-0'],
          'attributes' => [
              ['label' => 'ชื่อ - สกุล', 'value' => $fullName],
              ['label' => 'อีเมล', 'value' => (string)($model->email ?? '-')],
              ['label' => 'โทรศัพท์', 'value' => (string)($model->tel ?? '-')],
              ['label' => 'สังกัด', 'value' => $orgName],
              ['label' => 'สถานะ', 'value' => $posName],
          ],
      ]) ?>
    </div>
  </div>

  <!-- ===== Latest Lists (สีตามภาพตัวอย่าง) ===== -->
  <div class="row g-3">

    <div class="col-12">
      <?= $listCard(
          'งานวิจัย',
          'bi bi-journal-text',
          $researchLatest ?? [],
          'researchpro/view',
          ['projectID', 'research_id', 'id'],
          'projectID',
          ['projectNameTH', 'projectNameEN', 'projectName', 'title'],
          'researchpro/index',
          ['ResearchproSearch' => ['username' => (string)($model->username ?? '')]],
          '#7B2FF7',
          '#0A8BCB'
      ) ?>
    </div>

    <div class="col-12">
      <?= $listCard(
          'บทความ',
          'bi bi-file-earmark-text',
          $articleLatest ?? [],
          'article/view',
          ['article_id', 'id'],
          'article_id',
          ['article_th', 'article_en', 'title'],
          'article/index',
          ['ArticleSearch' => ['username' => (string)($model->username ?? '')]],
          '#F04646',
          '#C81D1D'
      ) ?>
    </div>

    <div class="col-12">
      <?= $listCard(
          'การนำไปใช้',
          'bi bi-lightbulb',
          $utilLatest ?? [],
          'utilization/view',
          ['utilization_id', 'util_id', 'id'],
          'utilization_id',
          ['project_name', 'title', 'util_title'],
          'utilization/index',
          ['UtilizationSearch' => ['username' => (string)($model->username ?? '')]],
          '#10B981',
          '#059669'
      ) ?>
    </div>

    <div class="col-12">
      <?= $listCard(
          'บริการวิชาการ',
          'bi bi-people',
          $serviceLatest ?? [],
          'academic-service/view',
          ['service_id', 'id'],
          'service_id',
          ['title', 'service_title', 'topic'],
          'academic-service/index',
          ['AcademicServiceSearch' => ['username' => (string)($model->username ?? '')]],
          '#0A8BCB',
          '#0067B8'
      ) ?>
    </div>

  </div>

</div>
