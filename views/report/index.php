<?php

use yii\helpers\Html;
use yii\helpers\Url;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */
/* @var $seriesY array */
/* @var $budgetSeriesY array */
/* @var $articleSeriesY array */
/* @var $utilSeriesY array */
/* @var $serviceSeriesY array */
/* @var $categoriesY array */
/* @var $seriesO array */
/* @var $categoriesO array */
/* @var $counttype1 int|string */
/* @var $counttype2 int|string */
/* @var $counttype3 int|string */
/* @var $counttype4 int|string */
/* @var $countuser int|string */
/* @var $isSelfRole bool */
/* @var $totalBudgets int|float */
/* @var $typeDonut array */
/* @var $statusDonut array */
/* @var $fundDonut array */
/* @var $fundingSeries array */
/* @var $fundingTotalNonZero array */
/* @var $articleByPubSeries array */
/* @var $articleByPubDonut array */
/* @var $totalArticleInRange int */
/* @var $totalProjects int */
/* @var $avgPerYear float */
/* @var $peakYear string|null */
/* @var $peakCount int */
/* @var $yearFrom int */
/* @var $yearTo int */

$this->title = 'รายงานภาพรวมงานวิจัย';
$this->params['breadcrumbs'][] = $this->title;

$currentYearTH = (int)date('Y') + 543;
$yearOptions = [];
for ($y = $currentYearTH; $y >= $currentYearTH - 15; $y--) {
    $yearOptions[$y] = $y;
}

// ===== Quick presets =====
$presetUrl = function ($from, $to) {
    return Url::to(['report/index', 'year_from' => $from, 'year_to' => $to]);
};
$presets = [
    ['label' => 'ปีนี้',  'sub' => (string)$currentYearTH,                   'from' => $currentYearTH,     'to' => $currentYearTH],
    ['label' => '3 ปี',   'sub' => ($currentYearTH-2).' - '.$currentYearTH,  'from' => $currentYearTH - 2, 'to' => $currentYearTH],
    ['label' => '5 ปี',   'sub' => ($currentYearTH-4).' - '.$currentYearTH,  'from' => $currentYearTH - 4, 'to' => $currentYearTH],
    ['label' => '10 ปี',  'sub' => ($currentYearTH-9).' - '.$currentYearTH,  'from' => $currentYearTH - 9, 'to' => $currentYearTH],
];

// ===== คำนวณ scope =====
$me = !Yii::$app->user->isGuest ? Yii::$app->user->identity : null;
$scopeIcon = 'fa-globe';
$scopeText = 'ภาพรวมทั้งระบบ';
if ($isSelfRole && $me) {
    $name = trim((string)$me->uname . ' ' . (string)$me->luname);
    $scopeText = ($name !== '' ? $name : (string)$me->username);
    $scopeIcon = 'fa-user';
} elseif (!Yii::$app->user->isGuest && $me && (int)$me->position !== 4) {
    $scopeText = 'ระดับหน่วยงาน';
    $scopeIcon = 'fa-building';
}

$totalBudgetText  = number_format((float)$totalBudgets, 0);
$totalProjectsText = number_format((int)$totalProjects);
$avgBudgetPerProject = $totalProjects > 0 ? ((float)$totalBudgets / $totalProjects) : 0;

// ===== Top 5 แหล่งทุน =====
$topFunding = $fundingTotalNonZero;
usort($topFunding, function ($a, $b) { return $b['total'] <=> $a['total']; });
$topFunding5 = array_slice($topFunding, 0, 5);
$maxFundingTotal = !empty($topFunding) ? max(array_column($topFunding, 'total')) : 1;

// ===== Top หน่วยงาน (จาก seriesO + categoriesO) =====
$orgPairs = [];
foreach ($categoriesO as $i => $oname) {
    if (($seriesO[$i] ?? 0) > 0) {
        $orgPairs[] = ['name' => $oname, 'count' => (int)$seriesO[$i]];
    }
}
usort($orgPairs, function ($a, $b) { return $b['count'] <=> $a['count']; });
$topOrg = array_slice($orgPairs, 0, 8);
$maxOrg = !empty($topOrg) ? max(array_column($topOrg, 'count')) : 1;
?>

<style>
/* ============================================================
 * REPORT DASHBOARD — Design System v2
 * ============================================================ */

:root {
    /* ----- Color: Primary (Indigo) ----- */
    --rp-primary:        #4f46e5;
    --rp-primary-600:    #4338ca;
    --rp-primary-soft:   #eef2ff;
    --rp-primary-border: #c7d2fe;

    /* ----- Color: Accent gradient ----- */
    --rp-grad-1: #4f46e5;  /* indigo */
    --rp-grad-2: #7c3aed;  /* violet */
    --rp-grad-3: #db2777;  /* pink */

    /* ----- Surface ----- */
    --rp-bg:        #f8fafc;
    --rp-card:      #ffffff;
    --rp-card-soft: #fafbfd;
    --rp-divider:   #f1f5f9;

    /* ----- Border ----- */
    --rp-border:        #e2e8f0;
    --rp-border-strong: #cbd5e1;

    /* ----- Text ----- */
    --rp-text:        #0f172a;  /* slate-900 — heading */
    --rp-text-2:      #1e293b;  /* slate-800 — body strong */
    --rp-text-3:      #475569;  /* slate-600 — body */
    --rp-muted:       #64748b;  /* slate-500 — secondary */
    --rp-muted-light: #94a3b8;  /* slate-400 — placeholder */

    /* ----- Shadow ----- */
    --rp-shadow-sm: 0 1px 2px rgba(15,23,42,.04);
    --rp-shadow:    0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.03);
    --rp-shadow-md: 0 4px 12px rgba(15,23,42,.08);
    --rp-shadow-lg: 0 10px 25px -5px rgba(15,23,42,.1), 0 8px 10px -6px rgba(15,23,42,.06);

    /* ----- Type scale (rem) ----- */
    --rp-fs-xs:  0.75rem;   /* 12px - micro */
    --rp-fs-sm:  0.825rem;  /* 13.2px - small */
    --rp-fs-base:0.9375rem; /* 15px  - body */
    --rp-fs-md:  1rem;      /* 16px  - card heading */
    --rp-fs-lg:  1.125rem;  /* 18px  - section heading */
    --rp-fs-xl:  1.5rem;    /* 24px  - hero */
    --rp-fs-2xl: 1.875rem;  /* 30px  - kpi value */
    --rp-fs-3xl: 2.25rem;   /* 36px  - hero on desktop */

    /* ----- Spacing ----- */
    --rp-gap:    1.25rem;
    --rp-gap-lg: 1.5rem;
    --rp-radius: .75rem;
}

/* Typography baseline — ใช้ system font ที่อ่านง่ายและรองรับไทย */
.report-index {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Sukhumvit Set",
                 "Sarabun", "Noto Sans Thai", "Helvetica Neue", Arial, sans-serif;
    font-size: var(--rp-fs-base);
    color: var(--rp-text-2);
    line-height: 1.55;
}
/* ตัวเลขในการ์ด/ตาราง — tabular-nums จัดเรียงเท่ากัน */
.report-index .num,
.report-index .kpi-value,
.report-index .top-count,
.report-index .ip-num {
    font-variant-numeric: tabular-nums;
    font-feature-settings: "tnum";
}

/* ===== Hero ===== */
.report-hero {
    background:
        radial-gradient(1200px 400px at -10% -50%, rgba(255,255,255,.18), transparent 60%),
        radial-gradient(800px 300px at 110% 110%, rgba(255,255,255,.12), transparent 60%),
        linear-gradient(135deg, var(--rp-grad-1) 0%, var(--rp-grad-2) 60%, var(--rp-grad-3) 100%);
    color: #fff;
    border-radius: 1rem;
    padding: 1.5rem 1.75rem;
    margin-bottom: var(--rp-gap);
    box-shadow: 0 12px 32px -10px rgba(79, 70, 229, .4);
    position: relative;
    overflow: hidden;
}
.report-hero::before {
    content:''; position:absolute; right:-80px; top:-80px;
    width:240px; height:240px; background:rgba(255,255,255,.08); border-radius:50%;
    pointer-events:none;
}
.report-hero h1 {
    font-size: var(--rp-fs-xl);
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0 0 .35rem 0;
    line-height: 1.2;
}
.report-hero .hero-sub {
    opacity: .9;
    font-size: var(--rp-fs-sm);
    letter-spacing: .01em;
}
.report-hero .hero-meta {
    display:inline-flex; align-items:center; gap:.4rem;
    background:rgba(255,255,255,.18); padding:.3rem .8rem;
    border-radius: 999px;
    font-size: var(--rp-fs-xs);
    font-weight: 500;
    margin: .25rem .35rem .25rem 0;
    backdrop-filter: blur(4px);
}
.report-hero .hero-meta i { font-size: .7rem; opacity: .9; }

.report-hero .quick-action-row {
    display:flex; gap:.5rem; flex-wrap:wrap;
    position:relative; z-index:1;
    margin-top: 1rem;
}
.report-hero .quick-link {
    background: rgba(255,255,255,.14);
    color: #fff;
    text-decoration: none;
    padding: .45rem .85rem;
    border-radius: 8px;
    font-size: var(--rp-fs-sm);
    font-weight: 500;
    border: 1px solid rgba(255,255,255,.22);
    transition: all .15s;
    display: inline-flex; align-items:center; gap:.4rem;
}
.report-hero .quick-link i { font-size: .85rem; }
.report-hero .quick-link:hover {
    background: rgba(255,255,255,.28);
    border-color: rgba(255,255,255,.4);
    transform: translateY(-1px);
}

/* ===== Sticky Filter Bar ===== */
.report-filter {
    position: sticky; top: 0; z-index: 1020;
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(10px);
    border-radius: var(--rp-radius);
    padding: .85rem 1rem;
    box-shadow: var(--rp-shadow);
    border: 1px solid var(--rp-border);
    margin-bottom: var(--rp-gap);
}
.report-filter .filter-hint {
    color: var(--rp-muted);
    font-size: var(--rp-fs-xs);
    font-weight: 500;
    margin-bottom: .5rem;
    display: inline-flex; align-items:center; gap:.4rem;
}
.report-filter .preset-chips a {
    display: inline-flex; flex-direction: column; align-items: center;
    line-height: 1.15;
    padding: .45rem .85rem;
    border-radius: 10px;
    background: var(--rp-divider);
    color: var(--rp-text-3);
    font-size: var(--rp-fs-sm);
    border: 1px solid var(--rp-border);
    text-decoration: none;
    transition: all .15s;
    margin-right: .4rem; margin-bottom: .3rem;
    min-width: 80px; text-align: center;
}
.report-filter .preset-chips a strong { font-weight: 600; }
.report-filter .preset-chips a small {
    font-size: var(--rp-fs-xs);
    color: var(--rp-muted-light);
    margin-top: 1px;
}
.report-filter .preset-chips a:hover {
    background: var(--rp-primary-soft);
    color: var(--rp-primary);
    border-color: var(--rp-primary-border);
    transform: translateY(-1px);
}
.report-filter .preset-chips a.active {
    background: linear-gradient(135deg, var(--rp-grad-1), var(--rp-grad-2));
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 10px -2px rgba(79,70,229,.4);
}
.report-filter .preset-chips a.active small { color: rgba(255,255,255,.85); }

.report-filter .form-label {
    font-size: var(--rp-fs-xs);
    font-weight: 500;
    color: var(--rp-muted);
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: .25rem !important;
}
.report-filter .form-select-sm { font-size: var(--rp-fs-sm); }
.report-filter .btn-sm { font-size: var(--rp-fs-sm); }

/* ===== KPI Cards ===== */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: .85rem;
    margin-bottom: var(--rp-gap);
}
.kpi-card {
    background: var(--rp-card);
    border-radius: var(--rp-radius);
    padding: 1.15rem 1.1rem;
    border: 1px solid var(--rp-border);
    transition: transform .18s, box-shadow .18s, border-color .18s;
    height: 100%;
    position: relative;
    display: flex; flex-direction: column;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rp-shadow-md);
    border-color: var(--rp-border-strong);
}
.kpi-card .kpi-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: .65rem; margin-bottom: .35rem;
}
.kpi-card .kpi-icon {
    width: 42px; height: 42px;
    border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 1.05rem;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(15,23,42,.1);
}
.kpi-card .kpi-label {
    color: var(--rp-muted);
    font-size: var(--rp-fs-xs);
    font-weight: 500;
    line-height: 1.3;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .25rem;
}
.kpi-card .kpi-value {
    font-size: var(--rp-fs-2xl);
    font-weight: 800;
    line-height: 1.1;
    color: var(--rp-text);
    letter-spacing: -.01em;
    word-break: break-word;
}
.kpi-card .kpi-unit {
    font-size: var(--rp-fs-sm);
    color: var(--rp-muted);
    font-weight: 500;
    margin-left: .15rem;
}
.kpi-card .kpi-link {
    font-size: var(--rp-fs-xs);
    color: var(--rp-primary);
    text-decoration: none;
    margin-top: auto;
    padding-top: .65rem;
    display: inline-flex; align-items: center; gap: .3rem;
    font-weight: 500;
}
.kpi-card .kpi-link:hover { color: var(--rp-primary-600); text-decoration: underline; }

/* KPI icon color variants (gradient) */
.kpi-icon.bg-indigo { background: linear-gradient(135deg, #818cf8, #4f46e5); }
.kpi-icon.bg-cyan   { background: linear-gradient(135deg, #22d3ee, #0891b2); }
.kpi-icon.bg-rose   { background: linear-gradient(135deg, #fb7185, #e11d48); }
.kpi-icon.bg-amber  { background: linear-gradient(135deg, #fbbf24, #d97706); }
.kpi-icon.bg-emerald{ background: linear-gradient(135deg, #34d399, #059669); }
.kpi-icon.bg-purple { background: linear-gradient(135deg, #c084fc, #7c3aed); }
.kpi-icon.bg-blue   { background: linear-gradient(135deg, #60a5fa, #1d4ed8); }
.kpi-icon.bg-pink   { background: linear-gradient(135deg, #f472b6, #be185d); }

/* ===== Section card ===== */
.section-card {
    background: var(--rp-card);
    border-radius: var(--rp-radius);
    border: 1px solid var(--rp-border);
    margin-bottom: var(--rp-gap);
    overflow: hidden;
    box-shadow: var(--rp-shadow-sm);
}
.section-card-header {
    padding: 1rem 1.2rem;
    border-bottom: 1px solid var(--rp-border);
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: .5rem;
    background: var(--rp-card-soft);
}
.section-card-header h5 {
    margin: 0;
    font-size: var(--rp-fs-md);
    color: var(--rp-text);
    font-weight: 700;
    display: flex; align-items: center; gap: .55rem;
    letter-spacing: -.005em;
}
.section-card-header h5 .icon-bg {
    display: inline-flex;
    width: 32px; height: 32px;
    background: var(--rp-primary-soft);
    color: var(--rp-primary);
    border-radius: 9px;
    align-items: center; justify-content: center;
    font-size: .9rem;
    flex-shrink: 0;
}
/* Section icon-bg color variants (soft) */
.icon-bg.indigo  { background: var(--rp-primary-soft); color: var(--rp-primary); }
.icon-bg.cyan    { background: #cffafe; color: #0e7490; }
.icon-bg.emerald { background: #d1fae5; color: #047857; }
.icon-bg.amber   { background: #fef3c7; color: #b45309; }
.icon-bg.rose    { background: #ffe4e6; color: #be123c; }
.icon-bg.violet  { background: #ede9fe; color: #6d28d9; }
.icon-bg.blue    { background: #dbeafe; color: #1d4ed8; }
.icon-bg.pink    { background: #fce7f3; color: #be185d; }

.section-card-header .meta {
    color: var(--rp-muted);
    font-size: var(--rp-fs-xs);
    font-weight: 500;
}
.section-card-body { padding: 1.15rem; }

/* ===== Top list ===== */
.top-list { padding: 0; margin: 0; list-style: none; }
.top-list li {
    padding: .65rem 0;
    border-bottom: 1px solid var(--rp-divider);
}
.top-list li:last-child { border-bottom: 0; padding-bottom: 0; }
.top-list .top-line {
    display: flex; align-items: center; gap: .55rem;
    margin-bottom: .35rem;
}
.top-list .top-rank {
    background: var(--rp-primary-soft);
    color: var(--rp-primary);
    width: 24px; height: 24px;
    border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: var(--rp-fs-xs);
    font-weight: 700;
    flex-shrink: 0;
}
.top-list .top-rank.gold {
    background: linear-gradient(135deg,#fef3c7,#fde68a);
    color: #b45309;
    box-shadow: 0 1px 3px rgba(180,83,9,.2);
}
.top-list .top-rank.silver {
    background: linear-gradient(135deg,#f1f5f9,#e2e8f0);
    color: #334155;
}
.top-list .top-rank.bronze {
    background: linear-gradient(135deg,#fee2e2,#fecaca);
    color: #b91c1c;
}
.top-list .top-name {
    flex: 1;
    font-size: var(--rp-fs-base);
    color: var(--rp-text-2);
    font-weight: 500;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.top-list .top-count {
    background: #ede9fe;
    color: #5b21b6;
    font-weight: 600;
    padding: .2rem .6rem;
    border-radius: 6px;
    font-size: var(--rp-fs-xs);
    flex-shrink: 0;
}
.top-list .top-bar {
    height: 6px;
    background: var(--rp-divider);
    border-radius: 4px;
    overflow: hidden;
}
.top-list .top-bar-inner {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(90deg, var(--rp-grad-1), var(--rp-grad-2));
    transition: width .4s ease-out;
}

/* ===== Empty state ===== */
.report-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--rp-muted-light);
}
.report-empty i {
    font-size: 2.75rem;
    margin-bottom: .85rem;
    color: var(--rp-border-strong);
}
.report-empty p { margin: .25rem 0 0; font-size: var(--rp-fs-sm); }
.report-empty h5 { color: var(--rp-text-3); font-weight: 600; }

/* ===== Highcharts container — smooth font ===== */
.highcharts-container { font-family: inherit !important; }
.highcharts-axis-labels text,
.highcharts-axis-title { font-size: var(--rp-fs-xs) !important; fill: var(--rp-muted) !important; }
.highcharts-data-label text { font-weight: 600 !important; }

/* ===== Print friendly ===== */
@media print {
    body { background: #fff !important; }
    .report-filter, .no-print, .quick-action-row { display: none !important; }
    .report-hero {
        background: var(--rp-grad-1) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        page-break-inside: avoid;
    }
    .section-card, .kpi-card {
        break-inside: avoid;
        page-break-inside: avoid;
        box-shadow: none;
    }
    .kpi-grid { grid-template-columns: repeat(3, 1fr); }
    .kpi-card .kpi-value { font-size: var(--rp-fs-xl); }
}

/* ===== Responsive type scale ===== */
@media (min-width: 992px) {
    .report-hero h1 { font-size: var(--rp-fs-3xl); }
    .kpi-card .kpi-value { font-size: var(--rp-fs-2xl); }
}

@media (max-width: 768px) {
    :root {
        --rp-fs-xl: 1.25rem;
        --rp-fs-2xl: 1.5rem;
        --rp-fs-3xl: 1.75rem;
        --rp-gap: 1rem;
    }
    .report-hero { padding: 1.1rem 1.2rem; }
    .report-hero .quick-action-row { gap: .35rem; }
    .report-hero .quick-link { padding: .35rem .65rem; font-size: var(--rp-fs-xs); }
    .report-filter { position: static; padding: .65rem .85rem; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: .65rem; }
    .kpi-card { padding: .9rem; }
    .kpi-card .kpi-icon { width: 36px; height: 36px; font-size: .9rem; }
    .section-card-body { padding: .85rem; }
}

@media (max-width: 480px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .report-hero .hero-meta { font-size: 11px; padding: .2rem .55rem; }
}
</style>

<div class="report-index pc-content">

    <!-- ============ Hero Header (compact) ============ -->
    <div class="report-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-2">
            <div>
                <h1>
                    <i class="fas fa-chart-pie me-2"></i>
                    รายงานภาพรวมงานวิจัย
                </h1>
                <div class="hero-sub">LASC SSKRU Research Management Dashboard</div>
            </div>

            <div>
                <span class="hero-meta">
                    <i class="fas fa-calendar-day"></i>
                    ปี <?= Html::encode($yearFrom) ?> – <?= Html::encode($yearTo) ?>
                </span>
                <span class="hero-meta">
                    <i class="fas <?= $scopeIcon ?>"></i>
                    <?= Html::encode($scopeText) ?>
                </span>
                <span class="hero-meta no-print">
                    <i class="fas fa-clock"></i>
                    <?= date('d/m/') . ((int)date('Y') + 543) . date(' H:i') ?>
                </span>
            </div>
        </div>

        <!-- Quick action: ลิงก์ไปโมดูลอื่น -->
        <div class="quick-action-row no-print">
            <a class="quick-link" href="<?= Url::to(['/researchpro/index']) ?>">
                <i class="fas fa-flask"></i> งานวิจัย
            </a>
            <a class="quick-link" href="<?= Url::to(['/article/index']) ?>">
                <i class="fas fa-newspaper"></i> การตีพิมพ์
            </a>
            <a class="quick-link" href="<?= Url::to(['/utilization/index']) ?>">
                <i class="fas fa-handshake-angle"></i> นำไปใช้ประโยชน์
            </a>
            <a class="quick-link" href="<?= Url::to(['/academic-service/index']) ?>">
                <i class="fas fa-hands-helping"></i> บริการวิชาการ
            </a>
            <a class="quick-link" href="<?= Url::to(['/account/index']) ?>">
                <i class="fas fa-users"></i> นักวิจัย
            </a>
        </div>
    </div>

    <!-- ============ Sticky Filter Bar ============ -->
    <div class="report-filter no-print">
        <form method="get" action="<?= Url::to(['report/index']) ?>" class="d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1">
                <div class="text-muted small mb-2">
                    <i class="fas fa-bolt text-warning me-1"></i> เลือกช่วงเวลา:
                </div>
                <div class="preset-chips">
                    <?php foreach ($presets as $p):
                        $isActive = ((int)$yearFrom === $p['from'] && (int)$yearTo === $p['to']);
                    ?>
                        <a href="<?= $presetUrl($p['from'], $p['to']) ?>" class="<?= $isActive ? 'active' : '' ?>">
                            <strong><?= Html::encode($p['label']) ?></strong>
                            <small><?= $p['sub'] ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="form-label small text-muted mb-1">ปีเริ่มต้น</label>
                    <?= Html::dropDownList('year_from', $yearFrom, $yearOptions, [
                        'class' => 'form-select form-select-sm',
                        'style' => 'min-width: 100px;',
                        'onchange' => 'this.form.submit()',
                    ]) ?>
                </div>
                <div>
                    <label class="form-label small text-muted mb-1">ปีสิ้นสุด</label>
                    <?= Html::dropDownList('year_to', $yearTo, $yearOptions, [
                        'class' => 'form-select form-select-sm',
                        'style' => 'min-width: 100px;',
                        'onchange' => 'this.form.submit()',
                    ]) ?>
                </div>
                <div>
                    <?= Html::a('<i class="fas fa-rotate-left"></i>', ['index'], [
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'encode' => false,
                        'title' => 'รีเซ็ตค่าเริ่มต้น',
                    ]) ?>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="พิมพ์/บันทึก PDF">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ============ KPI Cards (6) ============ -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="kpi-label">นักวิจัยทั้งหมด</div>
                    <div class="kpi-value"><?= is_numeric($countuser) ? number_format((int)$countuser) : Html::encode($countuser) ?></div>
                </div>
                <div class="kpi-icon bg-indigo"><i class="fas fa-user-friends"></i></div>
            </div>
            <a href="<?= Url::to(['/account/index']) ?>" class="kpi-link">ดูรายชื่อ <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="kpi-label">โครงการวิจัย (ในช่วง)</div>
                    <div class="kpi-value"><?= number_format((int)$totalProjects) ?> <span class="kpi-unit">โครงการ</span></div>
                </div>
                <div class="kpi-icon bg-cyan"><i class="fas fa-flask"></i></div>
            </div>
            <a href="<?= Url::to(['/researchpro/index']) ?>" class="kpi-link">ดูรายการ <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="kpi-label">บทความตีพิมพ์</div>
                    <div class="kpi-value"><?= number_format((int)$counttype4) ?></div>
                </div>
                <div class="kpi-icon bg-rose"><i class="fas fa-newspaper"></i></div>
            </div>
            <a href="<?= Url::to(['/article/index']) ?>" class="kpi-link">ดูบทความ <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="kpi-label">บริการวิชาการ</div>
                    <div class="kpi-value"><?= number_format((int)$counttype3) ?></div>
                </div>
                <div class="kpi-icon bg-emerald"><i class="fas fa-hands-helping"></i></div>
            </div>
            <a href="<?= Url::to(['/academic-service/index']) ?>" class="kpi-link">ดูรายการ <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="kpi-label">งบประมาณรวม</div>
                    <div class="kpi-value">
                        <?= number_format((float)$totalBudgets / 1000000, 2) ?>
                        <span class="kpi-unit">ล้านบาท</span>
                    </div>
                </div>
                <div class="kpi-icon bg-amber"><i class="fas fa-coins"></i></div>
            </div>
            <span class="kpi-link">ตลอดช่วง <?= $yearFrom ?>-<?= $yearTo ?></span>
        </div>

        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="kpi-label">เฉลี่ย/ปี</div>
                    <div class="kpi-value"><?= number_format($avgPerYear, 1) ?> <span class="kpi-unit">โครงการ</span></div>
                </div>
                <div class="kpi-icon bg-purple"><i class="fas fa-chart-line"></i></div>
            </div>
            <?php if ($peakYear): ?>
                <span class="kpi-link">ปี <?= Html::encode($peakYear) ?> สูงสุด (<?= (int)$peakCount ?>)</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($totalProjects === 0): ?>
        <div class="section-card">
            <div class="section-card-body report-empty">
                <i class="fas fa-folder-open"></i>
                <h5 class="mt-2">ไม่มีข้อมูลในช่วงปีที่เลือก</h5>
                <p>ลองเลือกช่วงเวลาอื่น หรือกดปุ่ม <i class="fas fa-rotate-left"></i> เพื่อรีเซ็ต</p>
            </div>
        </div>
    <?php else: ?>

    <!-- ============ แนวโน้มผลงาน 4 ประเภท รายปี (column) ============ -->
    <div class="section-card">
        <div class="section-card-header">
            <h5>
                <span class="icon-bg"><i class="fas fa-chart-column"></i></span>
                แนวโน้มผลงานรายปี (4 ประเภท)
            </h5>
            <span class="meta">
                เปรียบเทียบจำนวนรายการของแต่ละประเภทตามปี
            </span>
        </div>
<div class="section-card-body">
    <?= Highcharts::widget([
        'options' => [
            'accessibility' => ['enabled' => false],
            // 1. เปลี่ยน type จาก 'column' เป็น 'bar' ตรงนี้
            'chart' => ['type' => 'column', 'height' => 360, 'backgroundColor' => 'transparent'],
            'title' => ['text' => ''],
            'xAxis' => [
                'categories' => $categoriesY,
                'crosshair'  => true,
                'title'      => ['text' => 'ปี พ.ศ.'],
            ],
            'yAxis' => [
                'title'         => ['text' => 'จำนวน (รายการ)'],
                'allowDecimals' => false,
                'min'           => 0,
            ],
            'legend' => [
                'enabled'        => true,
                'align'          => 'center',
                'verticalAlign'  => 'bottom',
                'itemStyle'      => ['fontWeight' => '500', 'fontSize' => '12px'],
            ],
            'tooltip' => [
                'shared'      => true,
                'borderWidth' => 0,
                'shadow'      => true,
                'useHTML'     => true,
                'headerFormat'=> '<div style="font-weight:600;margin-bottom:4px;">ปี {point.key}</div>',
                'pointFormat' => '<span style="color:{point.color}">●</span> {series.name}: <b>{point.y}</b><br/>',
            ],
            'plotOptions' => [
                // 2. เปลี่ยนชื่อ key จาก 'column' เป็น 'bar' ตรงนี้
                'column' => [
                    'borderRadius' => 4,
                    'borderWidth'  => 0,
                    'pointPadding' => 0.1,
                    'groupPadding' => 0.12,
                    'dataLabels'   => [
                        'enabled' => true,
                        'style'   => ['fontWeight' => '600', 'fontSize' => '11px', 'textOutline' => 'none'],
                    ],
                ],
                'series' => [
                    'cursor' => 'pointer',
                    'states' => [
                        'inactive' => ['opacity' => 0.25],
                    ],
                ],
            ],
            'series' => [
                [
                    'name'  => 'งานวิจัย',
                    'color' => '#4f46e5',
                    'data'  => $seriesY,
                ],
                [
                    'name'  => 'การตีพิมพ์เผยแพร่',
                    'color' => '#f43f5e',
                    'data'  => $articleSeriesY,
                ],
                [
                    'name'  => 'การนำไปใช้ประโยชน์',
                    'color' => '#10b981',
                    'data'  => $utilSeriesY,
                ],
                [
                    'name'  => 'บริการวิชาการ',
                    'color' => '#f59e0b',
                    'data'  => $serviceSeriesY,
                ],
            ],
            'credits' => ['enabled' => false],
        ]
    ]); ?>
</div>
    </div>

    <!-- ============ การตีพิมพ์เผยแพร่บทความ แยกตามประเภทฐาน ============ -->
    <?php if (!empty($articleByPubSeries)): ?>
    <div class="section-card">
        <div class="section-card-header">
            <h5>
                <span class="icon-bg rose"><i class="fas fa-newspaper"></i></span>
                การตีพิมพ์เผยแพร่บทความ — แยกตามประเภทฐาน
            </h5>
            <span class="meta">
                รวม <strong><?= number_format((int)$totalArticleInRange) ?></strong> บทความ
                ใน <?= count($articleByPubDonut) ?> ประเภทฐาน
            </span>
        </div>
        <div class="section-card-body">
            <div class="row g-3">
                <!-- Stacked column รายปี -->
                <div class="col-12 col-lg-8">
                    <?= Highcharts::widget([
                        'options' => [
                            'accessibility' => ['enabled' => false],
                            'chart' => ['type' => 'column', 'height' => 360, 'backgroundColor' => 'transparent'],
                            'title' => ['text' => ''],
                            'xAxis' => [
                                'categories' => $categoriesY,
                                'crosshair'  => true,
                                'title'      => ['text' => 'ปี พ.ศ.'],
                            ],
                            'yAxis' => [
                                'title'         => ['text' => 'จำนวนบทความ'],
                                'allowDecimals' => false,
                                'min'           => 0,
                                'stackLabels'   => [
                                    'enabled' => true,
                                    'style'   => ['fontWeight' => '700', 'color' => '#475569', 'textOutline' => 'none'],
                                ],
                            ],
                            'legend' => [
                                'enabled'        => true,
                                'align'          => 'center',
                                'verticalAlign'  => 'bottom',
                                'itemStyle'      => ['fontWeight' => '500', 'fontSize' => '12px'],
                            ],
                            'tooltip' => [
                                'shared'       => true,
                                'borderWidth'  => 0,
                                'shadow'       => true,
                                'useHTML'      => true,
                                'headerFormat' => '<div style="font-weight:600;margin-bottom:4px;">ปี {point.key}</div>',
                                'pointFormat'  => '<span style="color:{point.color}">●</span> {series.name}: <b>{point.y}</b><br/>',
                                'footerFormat' => '<div style="border-top:1px solid #e5e7eb;margin-top:4px;padding-top:4px;">รวม: <b>{point.total}</b></div>',
                            ],
                            'plotOptions' => [
                                'column' => [
                                    'stacking'     => 'normal',
                                    'borderRadius' => 3,
                                    'borderWidth'  => 0,
                                    'dataLabels'   => [
                                        'enabled' => true,
                                        'style'   => ['fontWeight' => '600', 'fontSize' => '10px', 'textOutline' => 'none', 'color' => '#fff'],
                                        'filter'  => ['property' => 'y', 'operator' => '>', 'value' => 0],
                                    ],
                                ],
                                'series' => [
                                    'cursor' => 'pointer',
                                    'states' => [
                                        'inactive' => ['opacity' => 0.25],
                                    ],
                                ],
                            ],
                            'colors' => ['#4f46e5','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#0ea5e9','#84cc16'],
                            'series' => $articleByPubSeries,
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>

                <!-- Donut สัดส่วนรวม -->
                <div class="col-12 col-lg-4">
                    <?= Highcharts::widget([
                        'options' => [
                            'accessibility' => ['enabled' => false],
                            'chart' => ['type' => 'pie', 'height' => 360, 'backgroundColor' => 'transparent'],
                            'title' => [
                                'text'   => 'สัดส่วนรวม',
                                'style'  => ['fontSize' => '14px', 'fontWeight' => '600', 'color' => '#475569'],
                                'margin' => 8,
                            ],
                            'tooltip' => [
                                'pointFormat' => '<b>{point.y}</b> บทความ ({point.percentage:.1f}%)',
                            ],
                            'plotOptions' => [
                                'pie' => [
                                    'innerSize' => '60%',
                                    'dataLabels' => [
                                        'enabled' => true,
                                        'format'  => '{point.name}<br/><b>{point.y}</b>',
                                        'style'   => ['fontSize' => '11px', 'textOutline' => 'none'],
                                        'distance' => -25,
                                    ],
                                    'showInLegend' => false,
                                    'borderWidth'  => 2,
                                    'borderColor'  => '#fff',
                                ],
                            ],
                            'colors' => ['#4f46e5','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#0ea5e9','#84cc16'],
                            'series' => [['name' => 'บทความ', 'data' => $articleByPubDonut]],
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>

                    <!-- Legend แยกเป็น list ใต้ donut -->
                    <ol class="top-list mt-2">
                        <?php
                        $maxArt = !empty($articleByPubDonut) ? max(array_column($articleByPubDonut, 'y')) : 1;
                        $colorPalette = ['#4f46e5','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#0ea5e9','#84cc16'];
                        foreach ($articleByPubDonut as $i => $row):
                            if ((int)$row['y'] === 0) continue;
                            $color = $colorPalette[$i % count($colorPalette)];
                            $pct = $totalArticleInRange > 0 ? ($row['y'] / $totalArticleInRange) * 100 : 0;
                            $rankClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                        ?>
                            <li>
                                <div class="top-line">
                                    <span class="top-rank <?= $rankClass ?>">
                                        <i class="fas fa-circle" style="color:<?= $color ?>;font-size:.55rem;"></i>
                                    </span>
                                    <span class="top-name" title="<?= Html::encode($row['name']) ?>">
                                        <?= Html::encode($row['name']) ?>
                                    </span>
                                    <span class="top-count"><?= (int)$row['y'] ?></span>
                                </div>
                                <div class="top-bar">
                                    <div class="top-bar-inner" style="width:<?= ($row['y']/$maxArt)*100 ?>%;background:<?= $color ?>;"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============ Distribution: 3 Donuts ============ -->
    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <h5>
                        <span class="icon-bg"><i class="fas fa-project-diagram"></i></span>
                        ประเภทการวิจัย
                    </h5>
                </div>
                <div class="section-card-body">
                    <?php if (!empty($typeDonut)): ?>
                        <?= Highcharts::widget([
                            'options' => [
                                'accessibility' => ['enabled' => false],
                                'chart' => ['type' => 'pie', 'height' => 260, 'backgroundColor' => 'transparent'],
                                'title' => ['text' => ''],
                                'tooltip' => ['pointFormat' => '<b>{point.y}</b> โครงการ ({point.percentage:.1f}%)'],
                                'plotOptions' => [
                                    'pie' => [
                                        'innerSize' => '65%',
                                        'dataLabels' => [
                                            'enabled' => true,
                                            'format' => '{point.name}<br/>{point.y}',
                                            'style' => ['fontSize' => '11px'],
                                            'distance' => -28,
                                        ],
                                    ]
                                ],
                                'colors' => ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                                'series' => [['name' => 'โครงการ', 'data' => $typeDonut]],
                                'credits' => ['enabled' => false],
                            ]
                        ]); ?>
                    <?php else: ?>
                        <div class="report-empty"><i class="fas fa-circle-notch"></i><p>ไม่มีข้อมูล</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <h5>
                        <span class="icon-bg emerald"><i class="fas fa-tasks"></i></span>
                        สถานะงาน
                    </h5>
                </div>
                <div class="section-card-body">
                    <?php if (!empty($statusDonut)): ?>
                        <?= Highcharts::widget([
                            'options' => [
                                'accessibility' => ['enabled' => false],
                                'chart' => ['type' => 'pie', 'height' => 260, 'backgroundColor' => 'transparent'],
                                'title' => ['text' => ''],
                                'tooltip' => ['pointFormat' => '<b>{point.y}</b> โครงการ ({point.percentage:.1f}%)'],
                                'plotOptions' => [
                                    'pie' => [
                                        'innerSize' => '65%',
                                        'dataLabels' => [
                                            'enabled' => true,
                                            'format' => '{point.name}<br/>{point.y}',
                                            'style' => ['fontSize' => '11px'],
                                            'distance' => -28,
                                        ],
                                    ]
                                ],
                                'colors' => ['#10b981', '#f59e0b', '#ef4444', '#6b7280', '#06b6d4'],
                                'series' => [['name' => 'โครงการ', 'data' => $statusDonut]],
                                'credits' => ['enabled' => false],
                            ]
                        ]); ?>
                    <?php else: ?>
                        <div class="report-empty"><i class="fas fa-circle-notch"></i><p>ไม่มีข้อมูล</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <h5>
                        <span class="icon-bg amber"><i class="fas fa-coins"></i></span>
                        ประเภททุนวิจัย
                    </h5>
                </div>
                <div class="section-card-body">
                    <?php if (!empty($fundDonut)): ?>
                        <?= Highcharts::widget([
                            'options' => [
                                'accessibility' => ['enabled' => false],
                                'chart' => ['type' => 'pie', 'height' => 260, 'backgroundColor' => 'transparent'],
                                'title' => ['text' => ''],
                                'tooltip' => ['pointFormat' => '<b>{point.y}</b> โครงการ ({point.percentage:.1f}%)'],
                                'plotOptions' => [
                                    'pie' => [
                                        'innerSize' => '65%',
                                        'dataLabels' => [
                                            'enabled' => true,
                                            'format' => '{point.name}<br/>{point.y}',
                                            'style' => ['fontSize' => '11px'],
                                            'distance' => -28,
                                        ],
                                    ]
                                ],
                                'colors' => ['#f59e0b', '#db2777', '#8b5cf6', '#0ea5e9', '#10b981'],
                                'series' => [['name' => 'โครงการ', 'data' => $fundDonut]],
                                'credits' => ['enabled' => false],
                            ]
                        ]); ?>
                    <?php else: ?>
                        <div class="report-empty"><i class="fas fa-circle-notch"></i><p>ไม่มีข้อมูล</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ Top Org + Top Funding ============ -->
    <div class="row g-3">
        <!-- Top หน่วยงาน -->
        <div class="col-12 col-lg-7">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <h5>
                        <span class="icon-bg blue"><i class="fas fa-building"></i></span>
                        โครงการแยกตามหน่วยงาน
                    </h5>
                    <span class="meta"><?= count($topOrg) ?> หน่วยงาน</span>
                </div>
                <div class="section-card-body">
                    <?php if (!empty($topOrg)): ?>
                        <?= Highcharts::widget([
                            'options' => [
                                'accessibility' => ['enabled' => false],
                                'chart' => ['type' => 'bar', 'height' => max(280, count($topOrg) * 40), 'backgroundColor' => 'transparent'],
                                'title' => ['text' => ''],
                                'xAxis' => [
                                    'categories' => array_column($topOrg, 'name'),
                                    'title' => ['text' => null],
                                ],
                                'yAxis' => [
                                    'min' => 0,
                                    'title' => ['text' => 'จำนวนโครงการ', 'align' => 'high'],
                                    'allowDecimals' => false,
                                ],
                                'legend' => ['enabled' => false],
                                'tooltip' => ['pointFormat' => '<b>{point.y}</b> โครงการ'],
                                'plotOptions' => [
                                    'bar' => [
                                        'borderRadius' => 4, 'borderWidth' => 0,
                                        'dataLabels' => ['enabled' => true],
                                        'colorByPoint' => true,
                                    ]
                                ],
                                'colors' => ['#4f46e5','#7c3aed','#db2777','#ef4444','#f59e0b','#10b981','#06b6d4','#0ea5e9'],
                                'series' => [['name' => 'โครงการ', 'data' => array_column($topOrg, 'count')]],
                                'credits' => ['enabled' => false],
                            ]
                        ]); ?>
                    <?php else: ?>
                        <div class="report-empty"><i class="fas fa-building"></i><p>ไม่มีข้อมูลหน่วยงาน</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top 5 แหล่งทุน -->
        <div class="col-12 col-lg-5">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <h5>
                        <span class="icon-bg pink"><i class="fas fa-trophy"></i></span>
                        Top 5 แหล่งทุน
                    </h5>
                    <span class="meta"><?= count($topFunding) ?> แหล่ง</span>
                </div>
                <div class="section-card-body">
                    <?php if (!empty($topFunding5)): ?>
                        <ol class="top-list">
                            <?php foreach ($topFunding5 as $i => $ag):
                                $pct = ($ag['total'] / $maxFundingTotal) * 100;
                                $rankClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                            ?>
                                <li>
                                    <div class="top-line">
                                        <span class="top-rank <?= $rankClass ?>"><?= $i + 1 ?></span>
                                        <span class="top-name" title="<?= Html::encode($ag['name']) ?>">
                                            <?= Html::encode($ag['name']) ?>
                                        </span>
                                        <span class="top-count"><?= (int)$ag['total'] ?> โครงการ</span>
                                    </div>
                                    <div class="top-bar">
                                        <div class="top-bar-inner" style="width:<?= $pct ?>%;"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if (count($topFunding) > 5): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">และอีก <?= count($topFunding) - 5 ?> แหล่งทุน</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="report-empty"><i class="fas fa-coins"></i><p>ไม่มีแหล่งทุน</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ แหล่งทุนรายปี (stacked) ============ -->
    <?php if (!empty($fundingSeries) && count($fundingSeries) >= 1): ?>
    <div class="section-card">
        <div class="section-card-header">
            <h5>
                <span class="icon-bg emerald"><i class="fas fa-chart-line"></i></span>
                แนวโน้มแหล่งทุนรายปี
            </h5>
            <span class="meta">แสดงจำนวนโครงการของแต่ละแหล่งทุนตามปี</span>
        </div>
        <div class="section-card-body">
            <?= Highcharts::widget([
                'options' => [
                    'accessibility' => ['enabled' => false],
                    'chart' => ['type' => 'spline', 'height' => 360, 'backgroundColor' => 'transparent'],
                    'title' => ['text' => ''],
                    'xAxis' => [
                        'categories' => $categoriesY,
                        'crosshair'  => true,
                        'title'      => ['text' => 'ปี พ.ศ.'],
                    ],
                    'yAxis' => [
                        'min'           => 0,
                        'allowDecimals' => false,
                        'title'         => ['text' => 'จำนวนโครงการ'],
                    ],
                    'legend' => [
                        'enabled'        => true,
                        'align'          => 'center',
                        'verticalAlign'  => 'bottom',
                        'layout'         => 'horizontal',
                        'itemStyle'      => ['fontWeight' => '500', 'fontSize' => '12px'],
                        'maxHeight'      => 80,
                    ],
                    'tooltip' => [
                        'shared'       => true,
                        'borderWidth'  => 0,
                        'shadow'       => true,
                        'useHTML'      => true,
                        'headerFormat' => '<div style="font-weight:600;margin-bottom:4px;">ปี {point.key}</div>',
                        'pointFormat'  => '<span style="color:{point.color}">●</span> {series.name}: <b>{point.y}</b><br/>',
                    ],
                    'plotOptions' => [
                        'spline' => [
                            'lineWidth' => 2.5,
                            'marker'    => [
                                'enabled'   => true,
                                'radius'    => 4,
                                'symbol'    => 'circle',
                                'lineWidth' => 2,
                                'lineColor' => '#fff',
                            ],
                        ],
                        'series' => [
                            'cursor' => 'pointer',
                            'states' => [
                                'hover'    => ['lineWidth' => 3.5],
                                'inactive' => ['opacity' => 0.2],
                            ],
                        ],
                    ],
                    'colors' => ['#4f46e5','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#0ea5e9','#d946ef','#84cc16'],
                    'series' => $fundingSeries,
                    'credits' => ['enabled' => false],
                ]
            ]); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
