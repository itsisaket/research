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
 * Card List Builder (Bootstrap only, no custom CSS file)
 * - ชื่อแสดงเต็ม ไม่จำกัดบรรทัด
 * - รองรับ pk หลายชื่อ (fallback)
 * - ส่ง param name ให้ตรงกับ controller จริง
 * - เพิ่มสีสันด้วยธีม Bootstrap
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
    $theme = 'primary' // primary, success, warning, info, danger, secondary
) use ($getFirstNonEmpty) {

    $headerClass = "bg-{$theme}-subtle";
    $btnClass    = "btn btn-sm btn-outline-{$theme}";
    $borderClass = "border-start border-4 border-{$theme}";

    $headerRight = "<span class='text-muted small'>ล่าสุด 10 รายการ</span>";
    if ($indexRoute) {
        $headerRight = Html::a(
            "<i class='bi bi-list-ul'></i> ดูทั้งหมด",
            array_merge([$indexRoute], $indexParams),
            ['class' => $btnClass, 'encode' => false, 'data-pjax' => 0]
        );
    }

    $html = "<div class='card shadow-sm border-0 {$borderClass}'>
        <div class='card-header {$headerClass} d-flex justify-content-between align-items-center'>
            <div class='fw-semibold'><i class='{$icon}'></i> {$title}</div>
            {$headerRight}
        </div>
        <div class='card-body p-0'>";

    if (empty($items)) {
        $html .= "<div class='p-3 text-muted small'>ไม่มีข้อมูล</div>";
    } else {
        $html .= "<div class='list-group list-group-flush'>";

        foreach ($items as $m) {
            // pk fallback
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

<div class="container-fluid">

  <!-- ===== Header Profile ===== -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body bg-primary-subtle rounded">
      <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">

        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white shadow"
               style="width:56px;height:56px;">
            <span class="fw-bold"><?= Html::encode($initials($fullName)) ?></span>
          </div>

          <div>
            <div class="h5 mb-0"><?= Html::encode($fullName) ?></div>
            <div class="text-muted small">
              <i class="bi bi-person-badge"></i> <?= Html::encode((string)($model->username ?? '-')) ?>
              <span class="mx-2">•</span>
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

  <!-- ===== 1) Summary Counters ===== -->
  <div class="row g-3 mb-3">

    <div class="col-12 col-md-3">
      <div class="card shadow-sm border-0 border-start border-4 border-info">
        <div class="card-body bg-info-subtle">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><i class="bi bi-journal-text"></i> งานวิจัย</div>
              <div class="display-6 fw-bold mb-0"><?= (int)$cntResearch ?></div>
            </div>
            <i class="bi bi-journal-text fs-2 text-muted"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="card shadow-sm border-0 border-start border-4 border-success">
        <div class="card-body bg-success-subtle">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><i class="bi bi-file-earmark-text"></i> บทความ</div>
              <div class="display-6 fw-bold mb-0"><?= (int)$cntArticle ?></div>
            </div>
            <i class="bi bi-file-earmark-text fs-2 text-muted"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="card shadow-sm border-0 border-start border-4 border-warning">
        <div class="card-body bg-warning-subtle">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><i class="bi bi-lightbulb"></i> การนำไปใช้</div>
              <div class="display-6 fw-bold mb-0"><?= (int)$cntUtil ?></div>
            </div>
            <i class="bi bi-lightbulb fs-2 text-muted"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="card shadow-sm border-0 border-start border-4 border-primary">
        <div class="card-body bg-primary-subtle">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><i class="bi bi-people"></i> บริการวิชาการ</div>
              <div class="display-6 fw-bold mb-0"><?= (int)$cntService ?></div>
            </div>
            <i class="bi bi-people fs-2 text-muted"></i>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ===== 2) User Detail ===== -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-light">
      <strong><i class="bi bi-info-circle"></i> ข้อมูลผู้ใช้</strong>
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

  <!-- ===== 3) Latest Lists (เต็มแถว ไม่แบ่ง 2 คอลัมน์) ===== -->
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
          'info'
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
          'success'
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
          'warning'
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
          'primary'
      ) ?>
    </div>

  </div>

</div>
