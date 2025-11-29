<?php
use yii\helpers\Url;
use yii\helpers\Html;
use app\models\User as UserModel;

/**
 * ==============================
 * 1) เตรียมข้อมูลผู้ใช้
 * ==============================
 */
$user = Yii::$app->user;
$id   = $user->identity; // ถ้า guest จะเป็น null

// โปรไฟล์จาก identity หรือจาก JWT
$profile = [];
if ($id && isset($id->profile) && is_array($id->profile)) {
    $profile = $id->profile;
}

// ✅ ดึง JWT claims (fallback)
$claims = [];
if ($id && property_exists($id, 'access_token') && is_string($id->access_token)) {
    $claims = UserModel::decodeJwtPayload($id->access_token) ?: [];

    // ⭐ flatten JWT profile → claims root
    if (isset($claims['profile']) && is_array($claims['profile'])) {
        $claims = array_merge($claims, $claims['profile']);
    }
}

$usernameVal = $id->username ?? ($claims['username'] ?? null);
$prefixVal   = $id->prefix   ?? ($profile['title_name'] ?? ($claims['title_name'] ?? null));
$unameVal    = $id->uname    ?? ($profile['first_name'] ?? ($claims['first_name'] ?? null));
$lunameVal   = $id->luname   ?? ($profile['last_name']  ?? ($claims['last_name']  ?? null));
// ✅ ประกอบ displayName ให้เรียบร้อย
if ($user->isGuest) {
    $displayName = 'Guest';
} else {
    $displayName = 'Hi,: ';
}

$pic = $id->img ?? ($claims['img'] ?? null);

$fallback = Url::to('@web/template/berry/images/user/avatar-2.jpg');
$authenBase = Yii::$app->params['authenBase'] ?? ''; // เช่น 'https://sci-sskru.com/hrm/uploads/'
$avatarUrl = $fallback;


$ssoLoginUrl  = Yii::$app->params['ssoLoginUrl'] ?? 'https://sci-sskru.com/hrm/login';
$callbackPath = Url::to(['/site/index'], true);

$greetIconHtml = Html::tag('i', '', [
    'class'      => 'ti ti-user-circle me-2 align-text-bottom',
    'title'      => 'ผู้ใช้',
    'aria-label' => 'ผู้ใช้',
]);
?>

<!-- Header -->
<header class="pc-header">
  <div class="header-wrapper">

    <!-- Left: menu / mobile -->
    <div class="me-auto pc-mob-drp">
      <ul class="list-unstyled">
        <li class="pc-h-item header-mobile-collapse">
          <a href="#" class="pc-head-link head-link-secondary ms-0" id="sidebar-hide" aria-label="Toggle sidebar">
            <i class="ti ti-menu-2"></i>
          </a>
        </li>
        <li class="pc-h-item pc-sidebar-popup">
          <a href="#" class="pc-head-link head-link-secondary ms-0" id="mobile-collapse" aria-label="Toggle mobile menu">
            <i class="ti ti-menu-2"></i>
          </a>
        </li>
      </ul>
    </div>

    <!-- Right: user -->
    <div class="ms-auto">
      <ul class="list-unstyled mb-0">
        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link head-link-primary dropdown-toggle arrow-none me-0"
             data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
              <?= Html::img($avatarUrl, [
                  'alt'   => Html::encode($displayName),
                  'class' => 'user-avtar rounded-circle border border-2 border-white shadow-sm',
                  'style' => 'width:44px;height:44px;object-fit:cover;',
                  'onerror' => "this.onerror=null;this.src='" . Html::encode($fallback) . "';",
                  'title' => $displayName,
                  'id'    => 'nav-avatar',
              ]) ?>
            <span><i class="ti ti-settings"></i></span>
          </a>

          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">

            <?php if ($user->isGuest): ?>
              <!-- Guest -->
              <div class="dropdown-header">
                <h4 class="mb-1">
                  <?= $greetIconHtml ?>
                  <span class="small text-muted">Guest</span>
                </h4>
                <p class="text-muted mb-2">กรุณาเข้าสู่ระบบ</p>
                <hr class="my-2"/>
              </div>

              <?= Html::a(
                    '<i class="ti ti-lock"></i><span> เข้าสู่ระบบ</span>',
                    '#',
                    [
                      'class' => 'dropdown-item',
                      'encode' => false,
                      'data-pjax' => '0',
                      'id' => 'nav-login',
                      'data-sso-login' => $ssoLoginUrl,
                      'data-callback'  => $callbackPath,
                    ]
              ) ?>

            <?php else: ?>
              <!-- Logged-In -->
              <div class="dropdown-header">
                <h4 class="mb-1">
                  <?= $greetIconHtml ?>
                  <span class="small text-muted"><b><?= Html::encode($displayName) ?></b> <?= Html::encode($unameVal ?: '-') ?> <?= Html::encode($lunameVal ?: '-') ?></span>
                </h4>

                <?php if (!empty($displayRole)): ?>
                  <div class="text-muted small mb-1"><?= Html::encode($displayRole) ?></div>
                <?php endif; ?>

                <hr class="my-2"/>
              </div>
                  <?= $greetIconHtml ?>
                  <span class="small text-muted"><b><?= Html::encode($displayName) ?></b> <?= Html::encode($pic ?: '-') ?></span>
              <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 280px)">
                <?= Html::a(
                      '<i class="ti ti-settings"></i><span> ข้อมูลส่วนตัว</span>',
                      'https://sci-sskru.com/hrm/edit-personal',
                      [
                        'class' => 'dropdown-item',
                        'encode'=> false,
                        'data-pjax' => '0',
                        'target' => '_blank',
                        'rel' => 'noopener noreferrer',
                      ]
                ) ?>

                <?= Html::beginForm(['site/logout'], 'post', [
                      'class' => 'm-0',
                      'data-pjax' => '0',
                      'id' => 'nav-logout-form'
                ]) ?>
                <?= Html::submitButton(
                      '<i class="ti ti-logout"></i><span> ออกจากระบบ</span>',
                      ['class' => 'dropdown-item text-start', 'encode' => false, 'id' => 'nav-logout-btn']
                ) ?>
                <?= Html::endForm() ?>
              </div>
            <?php endif; ?>

          </div>
        </li>
      </ul>
    </div>
  </div>
</header>

<?php
$jsAuth = <<<JS
(function authSesNavbar(){
  const loginEl = document.getElementById('nav-login');
  if (loginEl) {
    loginEl.addEventListener('click', function(e){
      e.preventDefault();
      const sso = loginEl.dataset.ssoLogin || 'https://sci-sskru.com/hrm/login';
      const cb  = loginEl.dataset.callback  || '/site/index';
      const back = new URL(cb, window.location.origin).href;
      const u = new URL(sso, window.location.href);
      if (!u.searchParams.has('redirect')) {
        u.searchParams.set('redirect', back);
      }
      window.location.href = u.toString();
    });
  }

  const logoutBtn  = document.getElementById('nav-logout-btn');
  const logoutForm = document.getElementById('nav-logout-form');
  if (logoutBtn && logoutForm) {
    logoutBtn.addEventListener('click', function(){
      try {
        localStorage.removeItem('hrm-sci-token');
        localStorage.removeItem('userInfo');
        localStorage.removeItem('accessToken');
        sessionStorage.clear();
      } catch (e) {}
    });
  }
})();
JS;
$this->registerJs($jsAuth, \yii\web\View::POS_END);
?>
