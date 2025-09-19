<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use app\assets\BerryAsset;
BerryAsset::register($this);

/** ===== User & Profile ===== */
$user = Yii::$app->user;
$u    = $user->identity ?? null;
$p    = is_array($u->profile ?? null) ? $u->profile : [];

// ชื่อ-บทบาท
$nameFromProfile = trim(implode(' ', array_filter([
  $p['title_name'] ?? null,
  $p['first_name'] ?? null,
  $p['last_name']  ?? null,
])));
$displayName = $u
  ? ($nameFromProfile !== '' ? ('คุณ '.$nameFromProfile) : ($u->name ?? $u->username ?? 'Guest'))
  : 'Guest';
$role = $p['academic_type_name'] ?? $p['employee_type_name'] ?? $p['category_type_name'] ?? '';

// อวตาร
$avatar = Url::to('@web/template/berry/images/user/avatar-2.jpg');
if (!empty($p['img'])) {
  $avatar = preg_match('~^https?://~i', $p['img'])
    ? $p['img']
    : 'https://sci-sskru.com/authen/'.ltrim($p['img'], '/');
}

// ทักทายตามเวลา
$tz = new \DateTimeZone('Asia/Bangkok');
$h  = (int) (new \DateTime('now', $tz))->format('G');
$greet = $h < 12 ? 'Good Morning' : ($h < 18 ? 'Good Afternoon' : 'Good Evening');

// SSO / Auth endpoints
$ssoLoginUrl = Yii::$app->params['ssoLoginUrl']   ?? 'https://sci-sskru.com/hrm/login';
$jwtLoginUrl = Url::to(['/auth/jwt-login'], true);
$currentUrl  = Url::current([], true);
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Yii::$app->language ?>">
<head>
  <meta charset="<?= Yii::$app->charset ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= Html::encode($this->title) ?></title>
  <?= Html::csrfMetaTags() ?>
  <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<!-- Loader -->
<div class="loader-bg">
  <div class="loader-track"><div class="loader-fill"></div></div>
</div>

<!-- Sidebar -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <?= Html::img('@web/template/berry/images/logo-dark.svg', ['class' => 'icon-image', 'alt' => 'Logo']) ?>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">
        <li class="pc-item pc-caption">
          <label>Dashboard</label>
          <i class="ti ti-dashboard"></i>
        </li>
        <li class="pc-item">
          <a href="<?= Url::to(['/site/index']) ?>" class="pc-link">
            <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
            <span class="pc-mtext">หน้าหลัก</span>
          </a>
        </li>

        <li class="pc-item pc-caption">
          <label>ข้อมูลการวิจัยคณะ</label>
          <i class="ti ti-apps"></i>
        </li>
        <li class="pc-item">
          <a href="<?= Url::to(['/researcher/index']) ?>" class="pc-link">
            <span class="pc-micon"><i class="ti ti-typography"></i></span>
            <span class="pc-mtext">นักวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a href="<?= Url::to(['/paper/index']) ?>" class="pc-link">
            <span class="pc-micon"><i class="ti ti-color-swatch"></i></span>
            <span class="pc-mtext">งานวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a href="<?= Url::to(['/publication/index']) ?>" class="pc-link">
            <span class="pc-micon"><i class="ti ti-plant-2"></i></span>
            <span class="pc-mtext">การตีพิมพ์เผยแพร่</span>
          </a>
        </li>

        <li class="pc-item pc-caption">
          <label>สำหรับนักวิจัย</label>
          <i class="ti ti-news"></i>
        </li>
        <?php if ($user->isGuest): ?>
          <li class="pc-item">
            <a class="pc-link" href="<?= Url::to(['/site/login']) ?>">
              <span class="pc-micon"><i class="ti ti-lock"></i></span>
              <span class="pc-mtext">Login</span>
            </a>
          </li>
        <?php else: ?>
          <li class="pc-item">
            <a class="pc-link" href="<?= Url::to(['/site/my-profile']) ?>">
              <span class="pc-micon"><i class="ti ti-user"></i></span>
              <span class="pc-mtext">My Profile</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Header -->
<header class="pc-header">
  <div class="header-wrapper">
    <div class="me-auto pc-mob-drp">
      <ul class="list-unstyled">
        <li class="pc-h-item header-mobile-collapse">
          <a href="#" class="pc-head-link head-link-secondary ms-0" id="sidebar-hide">
            <i class="ti ti-menu-2"></i>
          </a>
        </li>
        <li class="pc-h-item pc-sidebar-popup">
          <a href="#" class="pc-head-link head-link-secondary ms-0" id="mobile-collapse">
            <i class="ti ti-menu-2"></i>
          </a>
        </li>
      </ul>
    </div>

    <div class="ms-auto">
      <ul class="list-unstyled">
        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link head-link-primary dropdown-toggle arrow-none me-0"
             data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
            <img id="nav-avatar" src="<?= Html::encode($avatar) ?>"
                 alt="user-image" class="user-avatar rounded-circle"
                 style="width:44px;height:44px;object-fit:cover;object-position:top;">
            <span><i class="ti ti-settings"></i></span>
          </a>

          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
            <div class="dropdown-header">
              <h4>
                <?= Html::encode($greet) ?>,
                <span class="small text-muted" id="nav-display-name"><?= Html::encode($displayName) ?></span>
              </h4>
              <?php if ($role): ?>
                <p class="text-muted" id="nav-role"><?= Html::encode($role) ?></p>
              <?php else: ?>
                <p class="text-muted" id="nav-role" style="display:none"></p>
              <?php endif; ?>
              <hr />
            </div>

            <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 280px)">
              <?php if ($user->isGuest): ?>
                <a href="<?= Url::to(['/site/login']) ?>" class="dropdown-item">
                  <i class="ti ti-lock"></i><span> Login</span>
                </a>
              <?php else: ?>
                <a href="<?= Url::to(['/site/my-profile']) ?>" class="dropdown-item">
                  <i class="ti ti-user"></i><span> My Profile</span>
                </a>
                <a href="https://sci-sskru.com/hrm/edit-personal" target="_blank" rel="noopener noreferrer" class="dropdown-item">
                  <i class="ti ti-settings"></i><span> Account Settings</span>
                </a>
                <?php
                echo Html::beginForm(['/site/logout'], 'post', ['class' => 'm-0', 'data-pjax' => '0']);
                echo Html::submitButton('<i class="ti ti-logout"></i><span> Logout</span>', [
                  'class' => 'dropdown-item text-start',
                  'encode' => false,
                  'data-action' => 'logout'
                ]);
                echo Html::endForm();
                ?>
              <?php endif; ?>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</header>

<!-- Main Content -->
<div class="pc-container">
  <div class="pc-content">
    <?= $content ?>
  </div>
</div>

<!-- Footer -->
<footer class="pc-footer">
  <div class="footer-wrapper container-fluid">
    <div class="row">
      <div class="col-sm-6 my-1">
        <p class="m-0">
          Berry &#9829; crafted by Team
          <a href="https://themeforest.net/user/codedthemes" target="_blank" rel="noopener">CodedThemes</a>
        </p>
      </div>
      <div class="col-sm-6 ms-auto my-1">
        <ul class="list-inline footer-link mb-0 justify-content-sm-end d-flex">
          <li class="list-inline-item"><a href="<?= Url::to(['/site/index']) ?>">Home</a></li>
          <li class="list-inline-item"><a href="https://codedthemes.gitbook.io/berry-bootstrap/" target="_blank" rel="noopener">Documentation</a></li>
          <li class="list-inline-item"><a href="https://codedthemes.support-hub.io/" target="_blank" rel="noopener">Support</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>

<!-- Script Configuration -->
<script>
  layout_change('light');
  font_change('Roboto');
  change_box_container('false');
  layout_caption_change('true');
  layout_rtl_change('false');
  preset_change('preset-1');

  // ลบ token ตอน logout
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-action="logout"]');
    if (btn) { try { localStorage.removeItem('hrm-sci-token'); } catch(_){} }
  });
</script>

<!-- Guard: สร้าง session เมื่อมี token / เด้งไป SSO เมื่อไม่มี -->
<?php
$hasSessionJs = $user->isGuest ? 'false' : 'true';
?>
<script>
(function(){
  const TOKEN_KEY  = 'hrm-sci-token';
  const hasSession = <?= $hasSessionJs ?>;
  const ssoLogin   = <?= Json::encode($ssoLoginUrl) ?>;
  const jwtLogin   = <?= Json::encode($jwtLoginUrl) ?>;
  const backUrl    = <?= Json::encode($currentUrl) ?>;

  const path = location.pathname.replace(/\/+$/, '');
  if (
    path.endsWith('/site/login') ||
    path.endsWith('/site/logout') ||
    path.endsWith('/site/index')   // ✅ allow index page
  ) return;



  const tok = localStorage.getItem(TOKEN_KEY);

  if (!tok && !hasSession) {
    location.replace(ssoLogin + '?redirect=' + encodeURIComponent(backUrl));
    return;
  }
  if (tok && !hasSession) {
    fetch(jwtLogin, {
      method: 'POST',
      headers: {'Content-Type':'application/json','Authorization':'Bearer ' + tok},
      body: JSON.stringify({token: tok})
    })
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(d => { if (d && d.ok) { location.reload(); } else { throw new Error('LOGIN_FAIL'); } })
    .catch(() => {
      try { localStorage.removeItem(TOKEN_KEY); } catch(_) {}
      location.replace(ssoLogin + '?redirect=' + encodeURIComponent(backUrl));
    });
  }
})();
</script>

<?php
$js = <<<JS
(function(){
  var logoutBtn  = document.getElementById('nav-logout-btn');
  var logoutForm = document.getElementById('nav-logout-form');
  if (logoutBtn && logoutForm) {
    logoutBtn.addEventListener('click', function(){
      try {
        localStorage.removeItem('hrm-sci-token');
        localStorage.removeItem('userInfo');
        localStorage.removeItem('accessToken');
        sessionStorage.clear();
      } catch (e) {}
      // ส่งฟอร์ม POST ต่อ
    });
  }
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>


<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
