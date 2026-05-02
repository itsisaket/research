<?php
/**
 * Sort dropdown partial
 *
 * @var array  $options  [['value'=>'-id','label'=>'ใหม่สุด','icon'=>'fa-arrow-down'], ...]
 * @var string $current  ค่าปัจจุบันจาก Yii::$app->request->get('sort')
 *
 * วิธีใช้:
 *   <?= $this->render('@app/views/_shared/_sort_dropdown', [
 *       'options' => [
 *           ['value' => '-id',   'label' => 'ใหม่สุด',  'icon' => 'fa-arrow-down-1-9'],
 *           ['value' => 'id',    'label' => 'เก่าสุด',  'icon' => 'fa-arrow-up-1-9'],
 *       ],
 *       'current' => Yii::$app->request->get('sort', '-id'),
 *   ]) ?>
 */

use yii\helpers\Html;
use yii\helpers\Url;

$options = $options ?? [];
$current = $current ?? '';

$activeOption = null;
foreach ($options as $opt) {
    if ((string)$opt['value'] === (string)$current) {
        $activeOption = $opt;
        break;
    }
}
if ($activeOption === null && !empty($options)) {
    $activeOption = $options[0];
}
?>

<div class="dropdown ss-sort">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
            data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-sort me-1"></i>
        <span class="d-none d-md-inline">เรียง:</span>
        <strong><?= Html::encode($activeOption['label'] ?? 'ค่าเริ่มต้น') ?></strong>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <?php foreach ($options as $opt):
            $params = Yii::$app->request->queryParams;
            $params['sort'] = $opt['value'];
            $isActive = ((string)$opt['value'] === (string)$current);
        ?>
            <li>
                <a class="dropdown-item <?= $isActive ? 'active' : '' ?>"
                   href="<?= Url::to(array_merge(['index'], $params)) ?>"
                   data-pjax="1">
                    <i class="fas <?= Html::encode($opt['icon'] ?? 'fa-circle-dot') ?>"></i>
                    <?= Html::encode($opt['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
