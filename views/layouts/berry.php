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

  // ล้าง token/flag ตอนกดปุ่ม logout
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
// ===== Guard / Auto-sync =====
// - หน้า index: เข้าได้เสมอ (ไม่ redirect). ถ้ามี token แต่ PHP ยัง guest → POST /site/my-profile แล้ว reload 1 ครั้ง
// - หน้าอื่น (ยกเว้น login/logout): ไม่มี session & token → redirect SSO; มี token แต่ไม่มี session → sync แล้ว reload
$user         = Yii::$app->user;
$hasSessionJs = $user->isGuest ? 'false' : 'true';
$ssoLoginUrl  = Yii::$app->params['ssoLoginUrl'] ?? 'https://sci-sskru.com/hrm/login';
$syncUrl      = Url::to(['/site/my-profile'], true);
$currentUrl   = Url::current([], true);
$csrfToken    = Yii::$app->request->getCsrfToken();
$baseUrl      = Yii::$app->request->baseUrl; // รองรับกรณีแอปอยู่ใน subfolder
?>
<script>
(function(){
  const TOKEN_KEY   = 'hrm-sci-token';
  const hasSession  = <?= $hasSessionJs ?>;
  const ssoLogin    = <?= Json::encode($ssoLoginUrl) ?>;
  const syncUrl     = <?= Json::encode($syncUrl) ?>;
  const backUrl     = <?= Json::encode($currentUrl) ?>;
  const csrfToken   = <?= Json::encode($csrfToken) ?>;
  const baseUrl     = <?= Json::encode($baseUrl) ?>;

  function parseJwt(t){
    if (!t) return null;
    const p = t.split('.'); if (p.length < 2) return null;
    try {
      let payload = p[1].replace(/-/g,'+').replace(/_/g,'/');
      const pad = payload.length % 4; if (pad) payload += '='.repeat(4 - pad);
      return JSON.parse(atob(payload));
    } catch { return null; }
  }

  const norm   = s => (s || '').replace(/\/+$/,'');
  const path   = norm(location.pathname);
  const params = new URLSearchParams(location.search);
  const base   = norm(baseUrl || '/');

  // ครอบคลุมทั้ง pretty URL, ?r=..., และกรณีแอปอยู่ใน subfolder
  const isLoginLike =
    path.endsWith('/site/login') || path.endsWith('/site/logout') ||
    params.get('r') === 'site/login' || params.get('r') === 'site/logout';

  const isIndex =
    params.get('r') === 'site/index' ||
    path.endsWith('/site/index')     ||
    path.endsWith('/index.php')      ||
    path === '' || path === '/'      ||
    path === base || path === base + '/';

  // อ่าน token
  let tok = null;
  try { tok = localStorage.getItem(TOKEN_KEY); } catch(_){ tok = null; }

  // --- หน้า index: ไม่ redirect; auto-sync ถ้าจำเป็น ---
  if (isIndex) {
    if (!hasSession && tok && sessionStorage.getItem('auto-sync-done') !== '1') {
      const claims = parseJwt(tok) || {};
      const now = Math.floor(Date.now()/1000), leeway = 120;
      if (claims.exp && (claims.exp + leeway) < now) {
        try { localStorage.removeItem(TOKEN_KEY); } catch(_){}
        return;
      }
      fetch(syncUrl, {
        method: 'POST',
        credentials: 'same-origin', // ส่งคุกกี้เพื่อให้สร้าง/อัปเดต PHP session
        headers: { 'Content-Type':'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ token: tok })
      })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(d => {
        if (d && d.ok) {
          sessionStorage.setItem('auto-sync-done', '1');
          location.reload();
        }
      })
      .catch(()=>{ /* เงียบ ๆ */ });
    }
    return; // ไม่ให้โค้ด redirect ด้านล่างทำงานบนหน้า index
  }

  // --- หน้าอื่น (ยกเว้น login/logout): บังคับตามเงื่อนไข ---
  if (!isLoginLike) {
    if (!hasSession && !tok) {
      location.replace(ssoLogin + '?redirect=' + encodeURIComponent(backUrl));
      return;
    }
    if (!hasSession && tok && sessionStorage.getItem('auto-sync-done') !== '1') {
      const claims = parseJwt(tok) || {};
      const now = Math.floor(Date.now()/1000), leeway = 120;
      if (claims.exp && (claims.exp + leeway) < now) {
        try { localStorage.removeItem(TOKEN_KEY); } catch(_){}
        location.replace(ssoLogin + '?redirect=' + encodeURIComponent(backUrl));
        return;
      }
      fetch(syncUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type':'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ token: tok })
      })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(d => {
        if (d && d.ok) {
          sessionStorage.setItem('auto-sync-done', '1');
          location.reload();
        } else {
          throw new Error('SYNC_FAIL');
        }
      })
      .catch(() => {
        try { localStorage.removeItem(TOKEN_KEY); } catch(_){}
        location.replace(ssoLogin + '?redirect=' + encodeURIComponent(backUrl));
      });
    }
  }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
