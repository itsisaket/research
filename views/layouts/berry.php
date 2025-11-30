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
      <?php
  // SweetAlert2 CDN
  $this->registerJsFile(
      'https://cdn.jsdelivr.net/npm/sweetalert2@11',
      ['position' => \yii\web\View::POS_HEAD]
  );
  ?>
</head>
<body data-pc-preset="preset-1">
<?php $this->beginBody() ?>

<?php
// ===== SweetAlert2 Flash Messages (show 3 seconds) =====
$flashes = Yii::$app->session->getAllFlashes(true);

foreach ($flashes as $type => $message) {

    $icon = in_array($type, ['success','error','warning','info','question'], true)
        ? $type
        : 'info';

    $jsMessage = \yii\helpers\Json::encode($message);
    $jsTitle   = \yii\helpers\Json::encode('แจ้งเตือน');

    $js = <<<JS
Swal.fire({
    icon: '{$icon}',
    title: {$jsTitle},
    text: {$jsMessage},
    timer: 5000,            // ⏱ แสดง 5 วินาที
    timerProgressBar: true, // แถบเวลา
    showConfirmButton: false
});
JS;

    $this->registerJs($js);
}
?>

<!-- ✅ Sidebar & Navbar -->
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

  // ล้าง token/flag ตอนกด logout
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

  const isLoginLike =
    path.endsWith('/site/login') || path.endsWith('/site/logout') ||
    params.get('r') === 'site/login' || params.get('r') === 'site/logout';

  // ฟังก์ชัน POST logout แล้วเด้งไป login
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
      const url = loginUrl + (loginUrl.includes('?') ? '&' : '?') + 'redirect=' + encodeURIComponent(backUrl);
      location.replace(url);
    }
  }

  // 1) ถ้าเป็นหน้า login/logout หรือ view ตั้งค่าว่าเป็น login page → ไม่ต้องเช็ก
  if (isLoginLike || isLoginPage) return;

  // 2) ถ้ายังไม่ได้ล็อกอิน (guest) → ปล่อยผ่านเลย
  //    ตรงนี้คือหัวใจของ "วิธีที่ 3" ที่คุณขอ
  if (!hasSession) return;

  // 3) ถึงตรงนี้แปลว่า "ล็อกอินแล้ว" → ต้องมี token
  try {
    const token = localStorage.getItem(KEY);
    const hasToken = !!(token && token.trim() !== '');

    if (!hasToken) {
      try { localStorage.removeItem(KEY); } catch(_) {}
      forceServerLogoutThenToLogin();
      return;
    }
  } catch(e) {
    // เข้าถึง storage ไม่ได้ แต่ล็อกอินอยู่ → ออกจากระบบ
    forceServerLogoutThenToLogin();
    return;
  }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
