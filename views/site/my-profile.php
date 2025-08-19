<?php
use yii\helpers\Html;
use yii\helpers\Json;
?>

<hr>
<h3>ข้อมูลทั้งหมด (All fields)</h3>

<table class="table table-striped table-sm">
  <thead>
    <tr>
      <th style="width: 280px;">Field</th>
      <th>Value</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ((array)$profile as $key => $value): ?>
    <tr>
      <th style="white-space: nowrap;"><?= Html::encode($key) ?></th>
      <td>
        <?php if (is_array($value) || is_object($value)): ?>
          <pre class="mb-0" style="white-space: pre-wrap;">
<?= Html::encode(Json::encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?>
          </pre>
        <?php else: ?>
          <?= Html::encode((string)$value) ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- ตัวเลือก: แสดง Raw JSON ทั้งก้อน + ปุ่มคัดลอก -->
<details class="mt-3">
  <summary>ดู Raw JSON</summary>
  <pre style="white-space: pre-wrap;"><?= Html::encode(Json::encode($profile, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="copy-json">คัดลอก JSON</button>
</details>

<?php
$jsJson = json_encode($profile, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$js = <<<JS
document.getElementById('copy-json')?.addEventListener('click', function(){
  navigator.clipboard.writeText('$jsJson').then(()=>alert('คัดลอกแล้ว'));
});
JS;
$this->registerJs($js);
?>
