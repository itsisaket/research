<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\assets\BerryAsset;
use app\models\User as UserModel;

BerryAsset::register($this);

$user  = Yii::$app->user;
$id    = $user->identity ?? null;
$profile = is_array($id->profile ?? null) ? $id->profile : [];

// ดึง claims จาก access_token ถ้ามี
$claims = [];
if ($id && property_exists($id, 'access_token') && is_string($id->access_token)) {
    $claims = UserModel::decodeJwtPayload($id->access_token) ?: [];
}

// สร้างชื่อที่จะแสดง
$title  = trim((string)($profile['title_name'] ?? $claims['title_name'] ?? ''));
$first  = trim((string)($profile['first_name'] ?? $claims['first_name'] ?? ''));
$last   = trim((string)($profile['last_name']  ?? $claims['last_name']  ?? ''));
$fullName = trim(($title ? $title.' ' : '') . trim($first.' '.$last));

// รูปโปรไฟล์ถ้ามี
$avatar = $profile['avatar'] ?? $claims['avatar'] ?? null;

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
<?= $this->render('_navbar', [
    'fullName' => $fullName,
    'avatar'   => $avatar,
]) ?>

<div class="pc-container">
  <div class="pc-content">
    <?= $content ?>
  </div>
</div>

<?= $this->render('_footer') ?>

<script>
(function() {
  const TOKEN_KEY  = 'accessToken';
  const SSO_LOGIN  = "<?= Url::to(['/site/login']) ?>";
  const BACK_URL   = window.location.href;

  function gotoLogin() {
    try {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem('hrm-sci-token');
      localStorage.removeItem('userInfo');
      sessionStorage.clear();
    } catch(e) {}
    window.location.replace(SSO_LOGIN + '?redirect=' + encodeURIComponent(BACK_URL));
  }

  try {
    // เช็ก token ถ้าต้องการ
    // const t = localStorage.getItem(TOKEN_KEY);
    // if (!t) gotoLogin();
  } catch(e) {
    gotoLogin();
  }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
