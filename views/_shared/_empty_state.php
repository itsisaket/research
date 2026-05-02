<?php
/**
 * Empty state — แสดงเมื่อไม่พบผลลัพธ์ตาม filter
 * วิธีใช้: ครอบ GridView แล้วเช็คก่อน:
 *
 *   <?php if ($dataProvider->getTotalCount() === 0): ?>
 *       <?= $this->render('@app/views/_shared/_empty_state', ['icon' => 'fa-folder-open']) ?>
 *   <?php else: ?>
 *       <?= GridView::widget([...]) ?>
 *   <?php endif; ?>
 *
 * @var string $icon       FA icon (default: fa-magnifying-glass)
 * @var string $title      หัวเรื่อง (default: 'ไม่พบรายการที่ค้นหา')
 * @var string $message    รายละเอียด
 * @var bool   $showReset  แสดงปุ่ม "ล้างตัวกรอง" ถ้ามี filter active
 */

use yii\helpers\Html;

$icon       = $icon ?? 'fa-magnifying-glass';
$title      = $title ?? 'ไม่พบรายการที่ตรงกับเงื่อนไข';
$message    = $message ?? 'ลองเปลี่ยนคำค้น หรือกดปุ่มด้านล่างเพื่อล้างตัวกรองทั้งหมด';
$showReset  = $showReset ?? true;

$hasFilter = !empty(array_filter(Yii::$app->request->queryParams, function ($v) {
    return is_array($v) ? !empty(array_filter($v)) : !empty($v);
}));
?>

<div class="ss-empty-state">
    <div class="ss-empty-icon"><i class="fas <?= Html::encode($icon) ?>"></i></div>
    <div class="ss-empty-title"><?= Html::encode($title) ?></div>
    <div class="text-muted"><?= Html::encode($message) ?></div>

    <?php if ($showReset && $hasFilter): ?>
        <div class="mt-3">
            <?= Html::a('<i class="fas fa-undo me-1"></i> ล้างตัวกรองทั้งหมด', ['index'], [
                'class' => 'btn btn-outline-primary',
                'encode' => false,
                'data-pjax' => 1,
            ]) ?>
        </div>
    <?php endif; ?>
</div>
