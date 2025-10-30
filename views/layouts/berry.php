<?php
use app\assets\BerryAsset;
use yii\helpers\Html;
use yii\helpers\Url;

BerryAsset::register($this);
$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
  <meta charset="<?= Yii::$app->charset ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= Html::csrfMetaTags() ?>
  <title><?= Html::encode($this->title ?: 'ระบบจัดการวิจัย LASC SSKRU 2025') ?></title>
  <?php $this->head() ?>
</head>
<body data-pc-preset="preset-1">
<?php $this->beginBody() ?>

<?= $this->render('_sidebar') ?>
<?= $this->render('_navbar') ?>

<div class="pc-container">
  <div class="pc-content">
    <?= $content ?>
  </div>
</div>

<?= $this->render('_footer') ?>

<script>
(function() {
  // ตั้งธีม light เป็นค่าเริ่มต้น
  try {
    if (typeof layout_change === 'function') {
      layout_change('light');
    }
  } catch (e) {}

  // ป้องกันกรณี token หมด / storage เพี้ยน → ให้เด้งไป login
  const TOKEN_KEYS = ['accessToken','hrm-sci-token','userInfo'];
  function forceLogout() {
    try {
      TOKEN_KEYS.forEach(k => localStorage.removeItem(k));
      sessionStorage.clear();
    } catch(e) {}
    window.location.href = "<?= Url::to(['/site/login']) ?>";
  }

  try {
    // ถ้าคุณต้องการบังคับว่าต้องมี token ให้เช็กตรงนี้
    // const t = localStorage.getItem('accessToken');
    // if (!t) forceLogout();
  } catch(e) {
    forceLogout();
  }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
