<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap4\Modal;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ArticleSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $contribCount array */
$contribCount = $contribCount ?? [];

$this->title = 'การตีพิมพ์เผยแพร่';
$this->params['breadcrumbs'][] = $this->title;

/* ===== helpers ===== */

/**
 * Format วันที่ → 'd/m/พ.ศ.'
 */
$fmtPublishDate = function ($v) {
    if (empty($v)) return null;
    $s = trim((string)$v);
    if (strpos($s, ' ') !== false) $s = explode(' ', $s)[0];

    $ts = false;
    foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt !== false) { $ts = $dt->getTimestamp(); break; }
    }
    if ($ts === false) $ts = strtotime($s);
    if ($ts === false) return Html::encode($s);

    return date('d/m/', $ts) . ((int)date('Y', $ts) + 543);
};

/**
 * ดึง URL ตัวแรกจาก field refer (ถ้ามี)
 * รองรับทั้ง http://, https://, www. (เพิ่ม https:// ให้ www. อัตโนมัติ)
 */
$extractUrl = function ($text) {
    if (empty($text)) return null;
    $s = (string)$text;
    if (preg_match('~https?://[^\s<>"\']+~i', $s, $m)) {
        return $m[0];
    }
    if (preg_match('~(?:^|\s)(www\.[^\s<>"\']+)~i', $s, $m)) {
        return 'https://' . $m[1];
    }
    return null;
};
?>

<style>
    /* ==== Article index — clean grid ==== */
    .ar-grid { font-size: .92rem; }
    .ar-grid > thead > tr > th {
        background: #fafbfd;
        color: #64748b;
        font-weight: 500;
        font-size: .82rem;
        border-bottom: 2px solid #e2e8f0;
        padding: .85rem .9rem;
        white-space: nowrap;
    }
    .ar-grid > tbody > tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
    .ar-grid > tbody > tr:hover { background: #fafbfd; }
    .ar-grid > tbody > tr > td { padding: 1rem .9rem; vertical-align: middle; }

    /* ชื่อบทความ */
    .ar-title {
        color: #0f172a;
        font-weight: 600;
        text-decoration: none;
        line-height: 1.45;
    }
    .ar-title:hover { color: #4f46e5; text-decoration: underline; }
    .ar-title-en {
        display: block;
        color: #64748b;
        font-size: .8rem;
        font-weight: 400;
        font-style: italic;
        margin-top: .15rem;
        line-height: 1.3;
    }

    /* sub-text ใต้ชื่อ */
    .ar-sub {
        margin-top: .35rem;
        font-size: .78rem;
        color: #64748b;
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }
    .ar-sub-item { display: inline-flex; align-items: center; gap: .25rem; }
    .ar-sub-item i { font-size: .75rem; opacity: .8; }

    /* badge ประเภทฐาน */
    .ar-pub-badge {
        display: inline-block;
        padding: .25rem .65rem;
        border-radius: 6px;
        font-size: .8rem;
        font-weight: 500;
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        white-space: nowrap;
    }

    /* วันที่ */
    .ar-date {
        color: #475569;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    /* วารสาร — link */
    .ar-journal {
        color: #1e293b;
        font-weight: 500;
        line-height: 1.35;
    }
    .ar-journal a.ar-journal-link {
        color: #1e293b;
        text-decoration: none;
        border-bottom: 1px dashed #94a3b8;
        transition: all .15s;
    }
    .ar-journal a.ar-journal-link:hover {
        color: #4f46e5;
        border-bottom-color: #4f46e5;
        border-bottom-style: solid;
    }
    .ar-journal a.ar-journal-link i {
        font-size: .7rem;
        margin-left: .25rem;
        opacity: .6;
    }
    .ar-journal-no-link { color: #1e293b; }

    /* Mobile */
    @media (max-width: 768px) {
        .ar-grid { font-size: .85rem; }
        .ar-grid > tbody > tr > td { padding: .75rem .6rem; }
    }
</style>

<div class="article-index">

    <p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

  <?php Pjax::begin([
      'id' => 'pjax-article',
      'timeout' => 8000,
      'clientOptions' => ['scrollTo' => false],
  ]); ?>

  <?php echo $this->render('_search', [ 'model' => $searchModel, 'pubItems' => $pubItems,]);?>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="text-muted small">
          พบทั้งหมด <strong><?= number_format($dataProvider->getTotalCount()) ?></strong> รายการ
      </div>
      <?php if (!Yii::$app->user->isGuest && $dataProvider->getTotalCount() > 0): ?>
          <?= Html::a(
              '<i class="fas fa-file-excel me-1"></i> ส่งออก Excel ตามผลค้นหา',
              array_merge(['export'], Yii::$app->request->queryParams),
              [
                  'class'   => 'btn btn-success btn-sm',
                  'encode'  => false,
                  'data-pjax' => 0,
                  'target'  => '_blank',
                  'rel'     => 'noopener',
                  'title'   => 'ดาวน์โหลด Excel ตามตัวกรองและคำค้นปัจจุบัน',
              ]
          ) ?>
      <?php endif; ?>
  </div>

  <div class="ss-grid-wrap">
    <?php if ($dataProvider->getTotalCount() === 0): ?>
        <?= $this->render('@app/views/_shared/_empty_state', [
            'icon'    => 'fa-newspaper',
            'title'   => 'ไม่พบบทความตามเงื่อนไข',
            'message' => 'ลองเปลี่ยนคำค้น หรือเลือกประเภทฐานอื่น หรือล้างตัวกรองทั้งหมด',
        ]) ?>
    <?php else: ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle ar-grid'],
        'columns' => [

            // ===== ชื่อบทความ (ไทย) + sub-text =====
            [
                'attribute' => 'article_th',
                'label' => 'ชื่อบทความ (ไทย)',
                'format' => 'raw',
                'contentOptions' => ['style' => 'min-width:300px;'],
                'value' => function ($model) use ($contribCount) {
                    $title = Html::a(
                        Html::encode($model->article_th),
                        ['view', 'article_id' => $model->article_id],
                        ['class' => 'ar-title', 'data-pjax' => 0, 'title' => 'คลิกเพื่อดูรายละเอียด']
                    );

                    // ชื่อ EN (ถ้ามี) เป็น sub
                    $en = trim((string)($model->article_eng ?? ''));
                    $enLine = $en !== ''
                        ? '<span class="ar-title-en">' . Html::encode($en) . '</span>'
                        : '';

                    // ผู้บันทึก + ผู้เขียนร่วม
                    $owner = '';
                    if ($model->user) {
                        $name = trim(($model->user->uname ?? '') . ' ' . ($model->user->luname ?? ''));
                        $owner = $name !== '' ? $name : $model->username;
                    }
                    $contribN = (int)($contribCount[(int)$model->article_id] ?? 0);

                    $sub = '<div class="ar-sub">';
                    if ($owner !== '') {
                        $sub .= '<span class="ar-sub-item"><i class="fas fa-user-edit"></i> '
                              . Html::encode($owner) . '</span>';
                    }
                    if ($contribN > 0) {
                        $sub .= '<span class="ar-sub-item"><i class="fas fa-users"></i> '
                              . $contribN . ' ผู้เขียนร่วม</span>';
                    }
                    $sub .= '</div>';

                    return $title . $enLine . $sub;
                },
            ],

            // ===== ประเภทฐาน =====
            [
                'attribute' => 'publication_type',
                'label' => 'ประเภทฐาน',
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:130px;'],
                'value' => function ($model) {
                    $name = $model->publi->publication_name ?? '';
                    if ($name === '') return '<span class="text-muted">—</span>';
                    return '<span class="ar-pub-badge">' . Html::encode($name) . '</span>';
                },
            ],

            // ===== วันที่เผยแพร่ =====
            [
                'attribute' => 'article_publish',
                'label' => 'วันที่เผยแพร่',
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:130px;'],
                'value' => function ($model) use ($fmtPublishDate) {
                    $d = $fmtPublishDate($model->article_publish);
                    return $d !== null
                        ? '<span class="ar-date">' . $d . '</span>'
                        : '<span class="text-muted">—</span>';
                },
            ],

            // ===== วารสาร/แหล่งเผยแพร่ — link ไป refer =====
            [
                'attribute' => 'journal',
                'label' => 'วารสาร/แหล่งเผยแพร่',
                'format' => 'raw',
                'contentOptions' => ['style' => 'min-width:240px;'],
                'value' => function ($model) use ($extractUrl) {
                    $journal = trim((string)($model->journal ?? ''));
                    if ($journal === '') return '<span class="text-muted">—</span>';

                    $url = $extractUrl($model->refer ?? '');

                    if ($url) {
                        return '<div class="ar-journal">'
                             . Html::a(
                                Html::encode($journal) . '<i class="fas fa-external-link-alt"></i>',
                                $url,
                                [
                                    'class'  => 'ar-journal-link',
                                    'target' => '_blank',
                                    'rel'    => 'noopener noreferrer',
                                    'title'  => 'เปิดลิงก์อ้างอิง: ' . $url,
                                    'data-pjax' => 0,
                                ]
                             )
                             . '</div>';
                    }

                    return '<div class="ar-journal"><span class="ar-journal-no-link">'
                         . Html::encode($journal) . '</span></div>';
                },
            ],

        ],
    ]); ?>
    <?php endif; ?>
  </div>

  <?php Pjax::end(); ?>

</div>
