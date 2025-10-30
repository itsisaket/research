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
  <title>ระบบจัดการวิจัย LASC SSKRU</title>
  <?php $this->head() ?>
</head>
<body data-pc-preset="preset-1">
<?php $this->beginBody() ?>

<!-- ✅ Sidebar & Navbar (partials self-contained) -->
<?= $this->render('_sidebar') ?>
<?= $this->render('_navbar') ?>

<!-- ✅ Main Content -->
<div class="pc-container">
  <div class="pc-content">
    <?= $content ?>
  </div>
</div>

<!-- ✅ Footer -->
<?= $this->render('_footer') ?>

<!-- ===== UI preset (ธีม Berry) ===== -->
<script>
  try {
    layout_change('light');
    font_change('Roboto');
    change_box_container('false');
    layout_caption_change('true');
    layout_rtl_change('false');
    preset_change('preset-1');
  } catch (e) {}

  // ล้าง token/flag ตอนกดปุ่ม logout (ปุ่มไหนก็ได้ที่ใส่ data-action="logout")
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
// ===== Guard & Token Enforcement =====
// หมายเหตุ: ถ้าเป็นหน้า login ให้ตั้ง $this->params['isLoginPage'] = true ใน view นั้น
$user          = Yii::$app->user;
$hasSessionJs  = $user->isGuest ? 'false' : 'true';
$loginUrl      = Url::to(['/site/login'], true);
$logoutUrl     = Url::to(['/site/logout'], true);  // POST
$currentUrl    = Url::current([], true);
$baseUrl       = Yii::$app->request->baseUrl;
$csrfParam     = Yii::$app->request->csrfParam;
$csrfToken     = Yii::$app->request->getCsrfToken();
$isLoginPage   = isset($this->params['isLoginPage']) && $this->params['isLoginPage'] === true;
?>
<script>
(function(){
  const KEY        = 'hrm-sci-token';
  const hasSession = <?= $hasSessionJs ?>;
  const isLoginPage= <?= $isLoginPage ? 'true' : 'false' ?>;
  const loginUrl   = <?= Json::encode($loginUrl) ?>;
  const logoutUrl  = <?= Json::encode($logoutUrl) ?>;
  const backUrl    = <?= Json::encode($currentUrl) ?>;
  const baseUrl    = <?= Json::encode($baseUrl) ?>;
  const csrfName   = <?= Json::encode($csrfParam) ?>;
  const csrfValue  = <?= Json::encode($csrfToken) ?>;

  const norm   = s => (s || '').replace(/\/+$/,'');
  const path   = norm(location.pathname);
  const params = new URLSearchParams(location.search);
  const base   = norm(baseUrl || '/');

  const isLoginLike =
    path.endsWith('/site/login') || path.endsWith('/site/logout') ||
    params.get('r') === 'site/login' || params.get('r') === 'site/logout';

  // ✅ นับเป็น index เมื่อเป็น root ของแอป, /index.php, /site/index หรือ ?r=site/index
  const isIndex =
    params.get('r') === 'site/index' ||
    path.endsWith('/site/index')     ||
    path.endsWith('/index.php')      ||
    path === '' || path === '/'      ||
    path === base || path === base + '/';

  // ฟังก์ชัน POST logout แบบปลอดภัย แล้วเด้งไป login พร้อม redirect เดิม
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
    } catch(e) {
      // fallback: ไปหน้า login พร้อม redirect
      const url = loginUrl + (loginUrl.includes('?') ? '&' : '?') + 'redirect=' + encodeURIComponent(backUrl);
      location.replace(url);
    }
  }

  // ⛳ หน้าล็อกอิน/ออกจากระบบ: ไม่ต้องเช็ค token ที่นี่
  if (isLoginLike || isLoginPage) return;

  // ⛳ หน้า index: ให้ผ่าน (คุณมี AccessControl ฝั่งเซิร์ฟเวอร์คุมเส้นทางอยู่แล้ว)
  //   *แพตเทิร์นของคุณคือ ต้องไป /site/login ก่อน แล้วค่อยมา index*
  //   ดังนั้น guard ฝั่ง server ต้องบังคับ guest → /site/login
  if (isIndex) {
    // ถ้าอยาก “เข้ม” เพิ่มเติม: ถ้าไม่มี token แต่มี session ค้าง → บังคับ logout
    try {
      const tok = localStorage.getItem(KEY);
      if (!tok || tok.trim() === '') {
        // ไม่มีโทเคนในเครื่อง แต่ user มี session? ตัดเซสชันทิ้งเพื่อความปลอดภัย
        if (<?= $hasSessionJs ?>) forceServerLogoutThenToLogin();
      }
    } catch(_) {}
    return;
  }

  // 🔐 หน้าอื่นทั้งหมด: ต้องมีทั้ง "session ฝั่ง server" และ "token ใน localStorage"
  // ถ้าอย่างใดอย่างหนึ่งขาด → ออกจากระบบทั้งหมด แล้วไปหน้า login
  try {
    const token = localStorage.getItem(KEY);
    const hasToken = !!(token && token.trim() !== '');

    if (!hasSession || !hasToken) {
      // เคลียร์ token ฝั่ง client เพื่อกันค้าง
      try { localStorage.removeItem(KEY); } catch(_) {}
      // ออกจากระบบฝั่งเซิร์ฟเวอร์ แล้วไป login
      forceServerLogoutThenToLogin();
      return;
    }
  } catch(e) {
    // กรณีเข้าถึง storage ไม่ได้ → บังคับออกจากระบบ
    forceServerLogoutThenToLogin();
    return;
  }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
