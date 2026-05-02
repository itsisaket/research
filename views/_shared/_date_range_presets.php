<?php
/**
 * Preset chips สำหรับ Date Range
 * แสดงชุดปุ่มลัด: 7 วัน / 30 วัน / ปีนี้ / ปีก่อน / 3 ปีล่าสุด
 *
 * @var \yii\base\Model $model       SearchModel
 * @var string $searchClass          ชื่อ Search class เช่น 'ResearchproSearch' (ใช้สร้าง URL)
 * @var string $label                (optional) label ก่อน chips
 *
 * วิธีใช้:
 *   <?= $this->render('@app/views/_shared/_date_range_presets', [
 *       'model'       => $model,
 *       'searchClass' => 'ResearchproSearch',
 *       'label'       => 'ช่วงเวลา:',
 *   ]) ?>
 */

use yii\helpers\Html;
use yii\helpers\Url;

$searchClass = $searchClass ?? '';
$label       = $label ?? 'ช่วงเวลา:';

$today    = date('Y-m-d');
$d7       = date('Y-m-d', strtotime('-7 days'));
$d30      = date('Y-m-d', strtotime('-30 days'));
$yearStart   = date('Y-01-01');
$yearEnd     = date('Y-12-31');
$prevYearS   = date('Y-01-01', strtotime('-1 year'));
$prevYearE   = date('Y-12-31', strtotime('-1 year'));
$threeYearsAgo = date('Y-01-01', strtotime('-3 years'));

$presets = [
    ['label' => '7 วันที่ผ่านมา',   'from' => $d7,            'to' => $today],
    ['label' => '30 วันที่ผ่านมา',  'from' => $d30,           'to' => $today],
    ['label' => 'ปีนี้',            'from' => $yearStart,     'to' => $yearEnd],
    ['label' => 'ปีก่อน',           'from' => $prevYearS,     'to' => $prevYearE],
    ['label' => '3 ปีล่าสุด',       'from' => $threeYearsAgo, 'to' => $today],
];

$buildUrl = function ($from, $to) use ($searchClass) {
    $params = Yii::$app->request->queryParams;
    if (empty($params[$searchClass]) || !is_array($params[$searchClass])) {
        $params[$searchClass] = [];
    }
    $params[$searchClass]['date_from'] = $from;
    $params[$searchClass]['date_to']   = $to;
    unset($params['page']);
    return Url::to(array_merge(['index'], $params));
};

$clearUrl = function () use ($searchClass) {
    $params = Yii::$app->request->queryParams;
    if (!empty($params[$searchClass])) {
        unset($params[$searchClass]['date_from'], $params[$searchClass]['date_to']);
    }
    unset($params['page']);
    return Url::to(array_merge(['index'], $params));
};

$currentFrom = $model->date_from ?? '';
$currentTo   = $model->date_to ?? '';
$hasRange = !empty($currentFrom) || !empty($currentTo);
?>

<div class="ss-presets ss-date-presets">
    <span class="ss-preset-label"><i class="fas fa-calendar-day me-1"></i> <?= Html::encode($label) ?></span>

    <?php foreach ($presets as $p):
        $isActive = ($currentFrom === $p['from'] && $currentTo === $p['to']);
    ?>
        <a href="<?= $isActive ? $clearUrl() : $buildUrl($p['from'], $p['to']) ?>"
           class="<?= $isActive ? 'active' : '' ?>"
           data-pjax="1"
           title="<?= Html::encode($p['from'] . ' ถึง ' . $p['to']) ?>">
            <?= Html::encode($p['label']) ?>
        </a>
    <?php endforeach; ?>

    <?php if ($hasRange): ?>
        <a href="<?= $clearUrl() ?>" class="text-danger" data-pjax="1" title="ล้างช่วงวันที่">
            <i class="fas fa-times-circle"></i>
        </a>
    <?php endif; ?>
</div>
