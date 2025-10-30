<?php
use app\assets\BerryAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

BerryAsset::register($this);
$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
  <meta charset="<?= Yii::$app->charset ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= Html::csrfMetaTags() ?>
  <title>ระบบจัดการวิจัย LASC SSKRU 2025</title>
  <?php $this->head() ?>
</head>
<body data-pc-preset="preset-1">
<?php $this->beginBody() ?>

<?php
// ✅ ตรวจว่าหน้านี้เป็นหน้า login หรือไม่
$isLoginPage = isset($this->params['isLoginPage']) && $this->params['isLoginPage'] === true;
?>

<?php if (!$isLoginPage): ?>
  <!-- ✅ Sidebar & Navbar -->
  <?= $this->render('_sidebar') ?>
  <?= $this->render('_navbar') ?>
<?php endif; ?>

<!-- ✅ Main Content -->
<div class="pc-container">
  <div class="pc-content">
    <?= $content ?>
  </div>
</div>

<?php if (!$isLoginPage): ?>
  <!-- ✅ Footer -->
  <?= $this->render('_footer') ?>
<?php endif; ?>

<!-- ✅ UI Preset (ธีม Berry) -->
<script>
  try {
    layout_change('light');
    font_change('Roboto');
    change_box_container('false');
    layout_caption_change('true');
    layout_rtl_change('false');
    preset_change('preset-1');
  } catch (e) {}

  // ✅ ล้าง token ตอนกดปุ่ม logout ใด ๆ ที่มี data-action="logout"
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-action="logout"]');
    if (btn) {
      try {
        localStorage.removeItem('hrm-sci-token');
        sessionStorage.removeItem('auto-sync-done');
      } catch(_) {}
    }
  });
</script>

<?php
// ===== Token + Session Guard =====
$user         = Yii::$app->user;
$hasSessionJs = $user->isGuest ? 'false' : 'true';
$loginUrl     = Url::to(['/site/login'], true);
$logoutUrl    = Url::to(['/site/logout'], true);
$currentUrl   = Url::current([], true);
$baseUrl      = Yii::$app->request->baseUrl;
$csrfParam    = Yii::$app->request->csrfParam;
$csrfToken    = Yii::$app->request->getCsrfToken();
?>
<script>
(function(){
  const KEY         = 'hrm-sci-token';
  const hasSession  = <?= $hasSessionJs ?>;
  const isLoginPage = <?= $isLoginPage ? 'true' : 'false' ?>;
  const loginUrl    = <?= Json::encode($loginUrl) ?>;
  const logoutUrl   = <?= Json::encode($logoutUrl) ?>;
  const backUrl     = <?= Json::encode($currentUrl) ?>;
  const baseUrl     = <?= Json::encode($baseUrl) ?>;
  const csrfName    = <?= Json::encode($csrfParam) ?>;
  const csrfValue   = <?= Json::encode($csrfToken) ?>;

  const normalize = s => (s || '').replace(/\/+$/,'');
  const path   = normalize(location.pathname);
  const params = new URLSearchParams(location.search);
  const base   = normalize(baseUrl || '/');

  const isLoginLike =
    path.endsWith('/site/login') || path.endsWith('/site/logout') ||
    params.get('r') === 'site/login' || params.get('r') === 'site/logout';

  // ✅ หน้าหลัก (index)
  const isIndex =
    params.get('r') === 'site/index' ||
    path.endsWith('/site/index')     ||
    path.endsWith('/index.php')      ||
    path === '' || path === '/'      ||
    path === base || path === base + '/';

  // ✅ ฟังก์ชัน logout ฝั่ง server แบบปลอดภัย
  function forceServerLogoutThenToLogin() {
    try {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = logoutUrl;

      const csrf = document.createElement('input');
      csrf.type  = 'hidden';
      csrf.name  = csrfName;
      csrf.value = csrfValue;
      form.appendChild(csrf);

      document.body.appendChild(form);
      form.submit();

      // fallback กรณี browser block form.submit()
      setTimeout(() => {
        const url = loginUrl + (loginUrl.includes('?') ? '&' : '?') + 'redirect=' + encodeURIComponent(backUrl);
        location.replace(url);
      }, 2000);
    } catch(e) {
      const url = loginUrl + (loginUrl.includes('?') ? '&' : '?') + 'redirect=' + encodeURIComponent(backUrl);
      location.replace(url);
    }
  }

  // ✅ หน้าล็อกอิน / logout → ไม่ต้องเช็ค token
  if (isLoginLike || isLoginPage) return;

  // ✅ หน้า index → ให้ผ่าน แต่ถ้าไม่มี token ให้ logout เพื่อความปลอดภัย
  if (isIndex) {
    try {
      const tok = localStorage.getItem(KEY);
      if (!tok || tok.trim() === '') {
        if (<?= $hasSessionJs ?>) forceServerLogoutThenToLogin();
      }
    } catch(_) {}
    return;
  }

  // ✅ หน้าที่เหลือ: ต้องมีทั้ง session และ token
  try {
    const token = localStorage.getItem(KEY);
    const hasToken = !!(token && token.trim() !== '');

    // 🔁 ถ้า session หาย แต่ token ยังอยู่ → ไปหน้า login เพื่อ re-sync
    if (!hasSession && hasToken) {
      location.replace(loginUrl + '?redirect=' + encodeURIComponent(backUrl));
      return;
    }

    // ❌ ถ้าไม่มี session หรือ token → logout ทิ้งทั้งหมด
    if (!hasSession || !hasToken) {
      try { localStorage.removeItem(KEY); } catch(_) {}
      forceServerLogoutThenToLogin();
      return;
    }
  } catch(e) {
    // กรณีเข้าถึง storage ไม่ได้ → logout เพื่อความปลอดภัย
    forceServerLogoutThenToLogin();
    return;
  }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
