<?php
use yii\helpers\Html;

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
/* @var $contribService array|null */

$this->title = 'โปรไฟล์ผู้ใช้';
$this->params['breadcrumbs'][] = ['label' => 'Accounts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/* =====================================================
 * Helpers (ปลอดภัย PHP 7.4)
 * ===================================================== */

$me = Yii::$app->user->identity ?? null;

$isAdmin = function () use ($me) {
    return ($me instanceof \app\models\Account)
        && in_array((int)($me->position ?? 0), [1, 4], true);
};

// ===== org / position (safe) =====
$orgName = '-';
$posName = '-';

try {
    $orgObj = $model->hasorg ?? null;
    if (is_object($orgObj) && isset($orgObj->org_name) && trim((string)$orgObj->org_name) !== '') {
        $orgName = (string)$orgObj->org_name;
    }
} catch (\Throwable $e) {}

try {
    $posObj = $model->hasposition ?? null;
    if (is_object($posObj) && isset($posObj->positionname) && trim((string)$posObj->positionname) !== '') {
        $posName = (string)$posObj->positionname;
    }
} catch (\Throwable $e) {}

$getVal = function ($obj, array $fields, $fallback = '-') {
    if (!is_object($obj)) return $fallback;
    foreach ($fields as $f) {
        if (isset($obj->$f) && trim((string)$obj->$f) !== '') {
            return (string)$obj->$f;
        }
    }
    return $fallback;
};

$getRelVal = function ($obj, string $rel, array $fields, $fallback = '-') {
    if (!is_object($obj)) return $fallback;
    try {
        $r = $obj->$rel ?? null;
        if (!is_object($r)) return $fallback;
        foreach ($fields as $f) {
            if (isset($r->$f) && trim((string)$r->$f) !== '') {
                return (string)$r->$f;
            }
        }
    } catch (\Throwable $e) {}
    return $fallback;
};

$fmtDate = function ($v) {
    if ($v === null || $v === '') return '-';
    try {
        $ts = is_numeric($v) ? (int)$v : strtotime((string)$v);
        return $ts ? date('d/m/Y', $ts) : '-';
    } catch (\Throwable $e) {
        return '-';
    }
};

$getId = function ($obj, array $pks) {
    if (!is_object($obj)) return null;
    foreach ($pks as $pk) {
        if (isset($obj->$pk) && $obj->$pk !== null && $obj->$pk !== '') {
            return $obj->$pk;
        }
    }
    return null;
};

$fullName = trim(($model->uname ?? '').' '.($model->luname ?? ''));
if ($fullName === '') $fullName = $model->username ?? '-';

$initials = function ($name) {
    $chars = preg_split('//u', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
    return mb_strtoupper(($chars[0] ?? '?').($chars[1] ?? ''), 'UTF-8');
};

$email = $model->email ?? '-';
$tel   = $model->tel ?? '-';

/* =====================================================
 * KPI Card
 * ===================================================== */
$kpiCard = function ($title, $value, $icon, $g1, $g2) {
    $title = Html::encode((string)$title);
    $value = Html::encode((string)$value);

    return "
    <div class='card border-0 shadow-sm rounded-4 overflow-hidden'
         style='background:linear-gradient(135deg,{$g1},{$g2});color:#fff'>
      <div class='card-body p-4'>
        <div class='d-flex justify-content-between'>
          <div>
            <div class='fw-semibold'>{$title}</div>
            <div class='display-6 fw-bold'>{$value}</div>
          </div>
          <div class='bg-white bg-opacity-25 rounded-3 p-3'>
            <i class='{$icon} fs-3'></i>
          </div>
        </div>
      </div>
    </div>";
};

/* =====================================================
 * Owner + Contributor Card
 * ===================================================== */
$workCard = function (
    $title, $icon,
    array $owners, array $contributors,
    $viewRoute, $paramName, array $pks,
    $indexRoute, array $indexParams,
    callable $metaCb,
    $g1, $g2
) use ($getId) {

    $titleSafe = Html::encode((string)$title);

    $header = "
    <div class='card-header border-0'
         style='background:linear-gradient(90deg,{$g1},{$g2})'>
      <div class='d-flex justify-content-between align-items-center'>
        <div class='fw-semibold text-white'>
          <i class='{$icon}'></i> {$titleSafe}
        </div>
        ".Html::a(
            '<i class="bi bi-list-ul"></i> ดูทั้งหมด',
            array_merge([$indexRoute], $indexParams),
            ['class'=>'btn btn-sm btn-light bg-white bg-opacity-25 border-0 text-white','encode'=>false,'data-pjax'=>0]
        )."
      </div>
    </div>";

    $body = "<div class='card-body p-0'>";

    $renderItem = function ($m, $badges = '') use ($viewRoute, $paramName, $pks, $metaCb, $getId) {
        $id = $getId($m, $pks);

        $name = Html::encode(
            $m->title ?? $m->projectNameTH ?? $m->article_th ?? $m->project_name ?? $m->topic ?? '-'
        );

        $meta = '';
        try {
            $meta = (string)call_user_func($metaCb, $m);
        } catch (\Throwable $e) {
            $meta = '-';
        }
        $metaSafe = $meta; // metaCb คืน HTML badge อยู่แล้ว

        $content = "
        <div class='d-flex justify-content-between align-items-start gap-2'>
          <div class='flex-grow-1'>
            <div class='fw-semibold'>{$name}</div>
            <div class='small text-muted mt-1'>{$metaSafe}</div>
            {$badges}
          </div>
          <i class='bi bi-box-arrow-up-right text-muted'></i>
        </div>";

        // ✅ ถ้าไม่มี id อย่าสร้างลิงก์
        if (!$id) {
            return "<div class='list-group-item py-2'>{$content}</div>";
        }

        return Html::a($content, [$viewRoute, $paramName => $id], [
            'class' => 'list-group-item list-group-item-action py-2',
            'encode' => false,
            'data-pjax' => 0,
        ]);
    };

    if (!empty($owners)) {
        $body .= "<div class='px-3 pt-3 fw-semibold text-muted'>
                    <i class='bi bi-star-fill text-warning'></i> เจ้าของผลงาน
                  </div>
                  <div class='list-group list-group-flush'>";
        foreach ($owners as $m) {
            $body .= $renderItem($m);
        }
        $body .= "</div>";
    }

    if (!empty($contributors)) {
        $body .= "<div class='px-3 pt-3 fw-semibold text-muted'>
                    <i class='bi bi-people-fill text-info'></i> ผู้ร่วมผลงาน
                  </div>
                  <div class='list-group list-group-flush'>";
        foreach ($contributors as $row) {
            $m = $row['model'] ?? null;
            if (!is_object($m)) continue;

            $role = $row['role'] ?? null;
            $pct  = $row['pct']  ?? null;

            $badge = "<div class='mt-1'>
                <span class='badge bg-secondary'>ผู้ร่วม</span>"
                . ($role ? " <span class='badge bg-info ms-1'>" . Html::encode((string)$role) . "</span>" : "")
                . (($pct !== null && $pct !== '') ? " <span class='badge bg-success ms-1'>" . Html::encode((string)$pct) . "%</span>" : "")
                . "</div>";

            $body .= $renderItem($m, $badge);
        }
        $body .= "</div>";
    }

    if (empty($owners) && empty($contributors)) {
        $body .= "<div class='p-3 text-muted small'>ไม่มีข้อมูล</div>";
    }

    $body .= "</div>";

    return "<div class='card border-0 shadow-sm rounded-4 overflow-hidden'>{$header}{$body}</div>";
};
?>

<div class="container-fluid py-2">

<!-- ===== Header ===== -->
<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-body p-4 d-flex justify-content-between align-items-center">
    <div class="d-flex gap-3 align-items-center">
      <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
           style="width:56px;height:56px;background:linear-gradient(135deg,#7B2FF7,#0A8BCB)">
        <strong><?= Html::encode($initials($fullName)) ?></strong>
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

    <div class="d-flex gap-2 flex-wrap">
      <?= Html::a('<i class="bi bi-arrow-left"></i> กลับ', ['index'], [
          'class' => 'btn btn-outline-secondary',
          'encode' => false,
          'data-pjax' => 0,
      ]) ?>
      <?php if ($isAdmin() && isset($model->uid)): ?>
        <?= Html::a('<i class="bi bi-pencil-square"></i> แก้ไข', ['update', 'id' => (int)$model->uid], [
            'class' => 'btn btn-primary',
            'encode' => false,
            'data-pjax' => 0,
        ]) ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ===== KPI ===== -->
<div class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-lg-3"><?= $kpiCard('งานวิจัย', (int)$cntResearch, 'bi bi-flask', '#0A8BCB', '#0067B8') ?></div>
  <div class="col-12 col-md-6 col-lg-3"><?= $kpiCard('บทความ', (int)$cntArticle, 'bi bi-file-earmark-text', '#F04646', '#C81D1D') ?></div>
  <div class="col-12 col-md-6 col-lg-3"><?= $kpiCard('การนำไปใช้', (int)$cntUtil, 'bi bi-lightbulb', '#10B981', '#059669') ?></div>
  <div class="col-12 col-md-6 col-lg-3"><?= $kpiCard('บริการวิชาการ', (int)$cntService, 'bi bi-people', '#6f42c1', '#4c1d95') ?></div>
</div>

<!-- ===== Cards ===== -->
<div class="row g-3">

  <div class="col-12">
    <?= $workCard(
        'งานวิจัย (เจ้าของ / ผู้ร่วม)',
        'bi bi-journal-text',
        $researchLatest ?? [],
        $contribResearch ?? [],
        'researchpro/view',
        'projectID',
        ['projectID','id','research_id'],
        'researchpro/index',
        ['ResearchproSearch' => ['username' => (string)($model->username ?? '')]],
        function ($m) use ($getRelVal, $getVal) {
            $fund = $getRelVal($m, 'fundingAgency', ['name','agency_name','fundingAgencyName'], '-');
            $year = $getVal($m, ['projectYearsubmit','year_submit','project_year'], '-');
            return "<span class='badge bg-light text-dark border me-1'>หน่วยงานทุน: " . Html::encode($fund) . "</span>"
                 . "<span class='badge bg-light text-dark border'>ปีเสนอ: " . Html::encode($year) . "</span>";
        },
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
        ['ArticleSearch' => ['username' => (string)($model->username ?? '')]],
        function ($m) use ($getRelVal, $getVal, $fmtDate) {
            $db = $getRelVal($m, 'publication', ['name','pub_name','title'], '-');
            $dt = $fmtDate($getVal($m, ['publish_date','published_at','public_date','article_date','date_publish'], ''));
            return "<span class='badge bg-light text-dark border me-1'>ประเภทฐาน: " . Html::encode($db) . "</span>"
                 . "<span class='badge bg-light text-dark border'>เผยแพร่: " . Html::encode($dt) . "</span>";
        },
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
        ['UtilizationSearch' => ['username' => (string)($model->username ?? '')]],
        function ($m) use ($getRelVal, $getVal, $fmtDate) {
            $type = $getRelVal($m, 'utilType', ['name','type_name'], '-');
            $dt   = $fmtDate($getVal($m, ['util_date','use_date','utilization_date','date_use'], ''));
            return "<span class='badge bg-light text-dark border me-1'>ประเภท: " . Html::encode($type) . "</span>"
                 . "<span class='badge bg-light text-dark border'>วันที่ใช้: " . Html::encode($dt) . "</span>";
        },
        '#10B981',
        '#059669'
    ) ?>
  </div>

  <div class="col-12">
    <?= $workCard(
        'บริการวิชาการ (เจ้าของ / ผู้ร่วม)',
        'bi bi-people',
        $serviceLatest ?? [],
        $contribService ?? [], // ✅ ถ้ามี ก็จะแสดงผู้ร่วมได้ทันที
        'academic-service/view',
        'service_id',
        ['service_id','id'],
        'academic-service/index',
        ['AcademicServiceSearch' => ['username' => (string)($model->username ?? '')]],
        function ($m) use ($getRelVal, $getVal, $fmtDate) {
            $type = $getRelVal($m, 'serviceType', ['type_name','name'], '-');
            $hrs  = $getVal($m, ['hours','hour','service_hour','total_hours'], '-');
            $dt   = $fmtDate($getVal($m, ['service_date','date','serviceDate'], ''));
            return "<span class='badge bg-light text-dark border me-1'>ประเภท: " . Html::encode($type) . "</span>"
                 . "<span class='badge bg-light text-dark border me-1'>ชั่วโมง: " . Html::encode($hrs) . "</span>"
                 . "<span class='badge bg-light text-dark border'>วันที่: " . Html::encode($dt) . "</span>";
        },
        '#6f42c1',
        '#4c1d95'
    ) ?>
  </div>

</div>
</div>
