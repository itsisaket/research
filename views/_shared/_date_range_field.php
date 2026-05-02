<?php
/**
 * Date Range field — ใช้ใน Advanced filter ของทุกโมดูลที่มีฟิลด์วันที่
 *
 * @var \yii\widgets\ActiveForm $form
 * @var \yii\base\Model $model     SearchModel ที่มี attribute date_from / date_to
 * @var string $label              ป้ายแสดง เช่น 'ช่วงวันที่เริ่มโครงการ'
 * @var string $hint               (optional) ข้อความใต้ field
 *
 * วิธีใช้:
 *   <?= $this->render('@app/views/_shared/_date_range_field', [
 *       'form'  => $form,
 *       'model' => $model,
 *       'label' => 'ช่วงวันที่เผยแพร่',
 *   ]) ?>
 */

use yii\helpers\Html;

$label = $label ?? 'ช่วงวันที่';
$hint  = $hint  ?? '';
?>

<label class="form-label small text-muted mb-1">
    <i class="fas fa-calendar-range me-1"></i> <?= Html::encode($label) ?>
</label>
<div class="input-group input-group-sm">
    <span class="input-group-text bg-light"><i class="fas fa-calendar-day"></i> จาก</span>
    <?= $form->field($model, 'date_from', [
        'template' => '{input}{error}',
        'options'  => ['class' => 'flex-grow-1'],
    ])->input('date', [
        'class' => 'form-control',
        'max'   => date('Y-m-d'),
    ])->label(false) ?>

    <span class="input-group-text bg-light"><i class="fas fa-calendar-check"></i> ถึง</span>
    <?= $form->field($model, 'date_to', [
        'template' => '{input}{error}',
        'options'  => ['class' => 'flex-grow-1'],
    ])->input('date', [
        'class' => 'form-control',
        'max'   => date('Y-m-d'),
    ])->label(false) ?>
</div>
<?php if ($hint !== ''): ?>
    <div class="form-text small"><?= Html::encode($hint) ?></div>
<?php endif; ?>
