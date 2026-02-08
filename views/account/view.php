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
/* @var $contribResearch array */
/* @var $contribArticle array */
/* @var $contribUtil array */

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

$email = $model->email ?? '-';
$tel   = $model->tel ?? '-';

// ✅ Account PK ของคุณคือ uid
$accountPkVal = $model->uid ?? null;

/**
 * KPI Card (gradient inline; no external css)
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
 * Combined Card: Owner + Contributor
 * - แสดง "เจ้าของผลงาน" + "ผู้ร่วมผลงาน" ในการ์ดเดียว
 * - ใช้ bootstrap + inline gradient เฉพาะ header
 * - ชื่อแสดงเต็ม (ไม่จำกัดบรรทัด) + encode ปลอดภัย
 */
$workCard = function (
    $title,
    $icon,
    array $ownerItems,
    array $contribItems,
    $viewRoute,
    $paramName,
    array $pkFields,
    $indexRoute = null,
    array $indexParams = [],
    callable $metaCb = null,          // ✅ เพิ่ม: สร้างบรรทัดข้อมูลประกอบ
    $g1 = '#6f42c1',
    $g2 = '#0d6efd'
) use ($getFirstNonEmpty) {

    $headStyle = "background: linear-gradient(90deg, {$g1}, {$g2});";

    $btnAll = "<span class='text-white-50 small'>ล่าสุด 10 รายการ</span>";
    if ($indexRoute) {
        $btnAll = Html::a(
            "<i class='bi bi-list-ul'></i> ดูทั้งหมด",
            array_merge([$indexRoute], $indexParams),
            [
                'class' => 'btn btn-sm btn-light bg-white bg-opacity-25 border-0 text-white',
                'encode' => false,
                'data-pjax' => 0,
            ]
        );
    }

    $getId = function ($obj) use ($pkFields) {
        if (!is_object($obj)) return null;
        foreach ($pkFields as $pk) {
            if (isset($obj->$pk) && $obj->$pk !== null && $obj->$pk !== '') {
                return $obj->$pk;
            }
        }
        return null;
    };

    $labelHtml = function ($text) {
        return "<div class='fw-semibold'>"
            . Html::encode((string)$text)
            . "</div>";
    };

    $html = "<div class='card border-0 shadow-sm rounded-4 overflow-hidden'>
        <div class='card-header border-0 py-3' style='{$headStyle}'>
          <div class='d-flex justify-content-between align-items-center'>
            <div class='fw-semibold text-white'><i class='{$icon}'></i> {$title}</div>
            <div>{$btnAll}</div>
          </div>
        </div>
        <div class='card-body p-0'>";

    $hasAny = false;

    // ===== เจ้าของ =====
    if (!empty($ownerItems)) {
        $hasAny = true;
        $html .= "<div class='px-3 pt-3 fw-semibold text-muted'>เจ้าของผลงาน</div>";
        $html .= "<div class='list-group list-group-flush'>";

        foreach ($ownerItems as $m) {
            $id   = $getId($m);
            $name = $getFirstNonEmpty($m, ['title','name','projectNameTH','article_th','project_name','topic'], '-');
            $meta = ($metaCb && is_object($m)) ? (string)call_user_func($metaCb, $m) : '';

            $content = "<div class='d-flex gap-2 align-items-start'>
                <div class='flex-grow-1'>
                    {$labelHtml($name)}
                    " . ($meta ? "<div class='small text-muted mt-1'>{$meta}</div>" : "") . "
                </div>
                <i class='bi bi-box-arrow-up-right text-muted'></i>
            </div>";

            if (!$id) {
                $html .= "<div class='list-group-item py-2'>{$content}</div>";
                continue;
            }

            $html .= Html::a($content, [$viewRoute, $paramName => $id], [
                'class' => 'list-group-item list-group-item-action py-2',
                'encode' => false,
                'data-pjax' => 0,
                'title' => strip_tags((string)$name),
            ]);
        }
        $html .= "</div>";
    }

    // ===== ผู้ร่วม =====
    if (!empty($contribItems)) {
        $hasAny = true;
        $html .= "<div class='px-3 pt-3 fw-semibold text-muted'>ผู้ร่วมผลงาน</div>";
        $html .= "<div class='list-group list-group-flush'>";

        foreach ($contribItems as $row) {
            $m    = $row['model'] ?? null;
            $role = $row['role'] ?? null;
            $pct  = $row['pct']  ?? null;

            if (!is_object($m)) continue;

            $id   = $getId($m);
            $name = $getFirstNonEmpty($m, ['title','name','projectNameTH','article_th','project_name','topic'], '-');
            $meta = ($metaCb) ? (string)call_user_func($metaCb, $m) : '';

            $badge = "<span class='badge bg-secondary'>ผู้ร่วม</span>";
            if ($role) $badge .= " <span class='badge bg-info'>" . Html::encode((string)$role) . "</span>";
            if ($pct !== null && $pct !== '') $badge .= " <span class='badge bg-success'>" . Html::encode((string)$pct) . "%</span>";

            $content = "<div class='d-flex justify-content-between align-items-start gap-2'>
                <div class='flex-grow-1'>
                    {$labelHtml($name)}
                    <div class='mt-1'>{$badge}</div>
                    " . ($meta ? "<div class='small text-muted mt-1'>{$meta}</div>" : "") . "
                </div>
                <i class='bi bi-box-arrow-up-right text-muted'></i>
            </div>";

            if (!$id) {
                $html .= "<div class='list-group-item py-2'>{$content}</div>";
                continue;
            }

            $html .= Html::a($content, [$viewRoute, $paramName => $id], [
                'class' => 'list-group-item list-group-item-action py-2',
                'encode' => false,
                'data-pjax' => 0,
                'title' => strip_tags((string)$name),
            ]);
        }
        $html .= "</div>";
    }

    if (!$hasAny) {
        $html .= "<div class='p-3 text-muted small'>ไม่มีข้อมูล</div>";
    }

    $html .= "</div></div>";
    return $html;
};

?>

<div class="container-fluid py-2">

  <!-- ===== Header Profile ===== -->
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
            <div class="text-muted small">
              <i class="bi bi-envelope"></i> <?= Html::encode((string)$email) ?>
              <span class="mx-2">•</span>
              <i class="bi bi-telephone"></i> <?= Html::encode((string)$tel) ?>
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

  <!-- ===== Top KPI Row ===== -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <?= $kpiCard('โครงการวิจัย', (int)$cntResearch, 'bi bi-flask', '#0A8BCB', '#0067B8') ?>
    </div>
    <div class="col-12 col-md-4">
      <?= $kpiCard('บทความวิจัย', (int)$cntArticle, 'bi bi-file-earmark-text', '#F04646', '#C81D1D') ?>
    </div>
    <div class="col-12 col-md-4">
      <?= $kpiCard('การนำไปใช้', (int)$cntUtil, 'bi bi-lightbulb', '#10B981', '#059669') ?>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
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

<?php
  // งานวิจัย: ชื่อโครงการภาษาไทย, หน่วยงานทุน, ปีเสนอ
$metaResearch = function($m) use ($getVal, $getRelVal) {
    $fund = $getRelVal($m, 'fundingAgency', ['name','agency_name','fundingAgencyName'], null);
    if ($fund === null) $fund = $getVal($m, ['fundingAgencyName','funding_agency','agency_name'], '-');

    $year = $getVal($m, ['projectYearsubmit','year_submit','project_year'], '-');

    return "<span class='badge bg-light text-dark border me-1'>หน่วยงานทุน: ".Html::encode($fund)."</span>"
        . "<span class='badge bg-light text-dark border'>ปีเสนอ: ".Html::encode($year)."</span>";
};

// บทความ: ชื่อบทความ(ไทย), ประเภทฐาน, วันที่เผยแพร่
$metaArticle = function($m) use ($getVal, $getRelVal, $fmtDate) {
    $db = $getRelVal($m, 'publication', ['name','pub_name','title'], null);
    if ($db === null) $db = $getVal($m, ['db_type','database_type','index_type','publication_type','publication'], '-');

    $dt = $getVal($m, ['publish_date','published_at','public_date','article_date','date_publish'], null);
    $dt = $fmtDate($dt);

    return "<span class='badge bg-light text-dark border me-1'>ประเภทฐาน: ".Html::encode($db)."</span>"
        . "<span class='badge bg-light text-dark border'>เผยแพร่: ".Html::encode($dt)."</span>";
};

// การนำไปใช้: ชื่อโครงการ/ผลงาน, ประเภทการใช้ประโยชน์, วันที่ใช้ประโยชน์
$metaUtil = function($m) use ($getVal, $getRelVal, $fmtDate) {
    $type = $getRelVal($m, 'utilType', ['name','type_name'], null);
    if ($type === null) $type = $getVal($m, ['util_type','type_name','utilization_type','util_type_name'], '-');

    $dt = $getVal($m, ['util_date','use_date','utilization_date','date_use'], null);
    $dt = $fmtDate($dt);

    return "<span class='badge bg-light text-dark border me-1'>ประเภท: ".Html::encode($type)."</span>"
        . "<span class='badge bg-light text-dark border'>วันที่ใช้: ".Html::encode($dt)."</span>";
};

// บริการวิชาการ: เรื่อง, ประเภท, ชั่วโมง, วันที่
$metaService = function($m) use ($getVal, $getRelVal, $fmtDate) {
    $type = $getRelVal($m, 'serviceType', ['type_name','name'], null);
    if ($type === null) $type = $getVal($m, ['type_name','service_type','serviceTypeName'], '-');

    $hrs = $getVal($m, ['hours','hour','service_hour','total_hours'], '-');

    $dt = $getVal($m, ['service_date','date','serviceDate'], null);
    $dt = $fmtDate($dt);

    return "<span class='badge bg-light text-dark border me-1'>ประเภท: ".Html::encode($type)."</span>"
        . "<span class='badge bg-light text-dark border me-1'>ชั่วโมง: ".Html::encode($hrs)."</span>"
        . "<span class='badge bg-light text-dark border'>วันที่: ".Html::encode($dt)."</span>";
};
?>
  <!-- ===== Combined Cards: เจ้าของ/ผู้ร่วม ===== -->
<div class="row g-3">

  <div class="col-12">
    <?= $workCard(
        'งานวิจัย (เจ้าของ / ผู้ร่วม)',
        'bi bi-journal-text',
        $researchLatest ?? [],
        $contribResearch ?? [],
        'researchpro/view',
        'projectID',
        ['projectID','research_id','id'],
        'researchpro/index',
        ['ResearchproSearch'=>['username'=>(string)($model->username ?? '')]],
        $metaResearch,
        '#7B2FF7',
        '#0A8BCB'
    ) ?>
  </div>

  <div class="col-12">
    <?= $workCard(
        'บทความ (เจ้าของ / ผู้ร่วม)',
        'bi bi-file-earmark-text',
        $articleLatest ?? [],
        $contribArticle ?? [],
        'article/view',
        'article_id',
        ['article_id','id'],
        'article/index',
        ['ArticleSearch'=>['username'=>(string)($model->username ?? '')]],
        $metaArticle,
        '#F04646',
        '#C81D1D'
    ) ?>
  </div>

  <div class="col-12">
    <?= $workCard(
        'การนำไปใช้ (เจ้าของ / ผู้ร่วม)',
        'bi bi-lightbulb',
        $utilLatest ?? [],
        $contribUtil ?? [],
        'utilization/view',
        'utilization_id',
        ['utilization_id','util_id','id'],
        'utilization/index',
        ['UtilizationSearch'=>['username'=>(string)($model->username ?? '')]],
        $metaUtil,
        '#10B981',
        '#059669'
    ) ?>
  </div>

  <div class="col-12">
    <?= $workCard(
        'บริการวิชาการ (เจ้าของ / ผู้ร่วม)',
        'bi bi-people',
        $serviceLatest ?? [],
        [], // ถ้ายังไม่ทำ contributor ของบริการวิชาการ
        'academic-service/view',
        'service_id',
        ['service_id','id'],
        'academic-service/index',
        ['AcademicServiceSearch'=>['username'=>(string)($model->username ?? '')]],
        $metaService,
        '#0A8BCB',
        '#0067B8'
    ) ?>
  </div>

</div>

</div>
