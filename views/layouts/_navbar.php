<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\User as UserModel;

/** ==============================
 *  เตรียมข้อมูลผู้ใช้
 * ============================== */
$user = Yii::$app->user;
$id   = $user->identity ?? null;

$profile = is_array($id->profile ?? null) ? $id->profile : [];

/* ✅ ดึง JWT claims (fallback) */
$claims = [];
if ($id && property_exists($id, 'access_token') && is_string($id->access_token)) {
    $claims = UserModel::decodeJwtPayload($id->access_token) ?: [];
}

/** ==============================
 *  กำหนดชื่อแสดงผล
 * ============================== */
$title = trim((string)($profile['title_name'] ?? $claims['title_name'] ?? ''));
$first = trim((string)($profile['first_name'] ?? $claims['first_name'] ?? ''));
$last  = trim((string)($profile['last_name']  ?? $claims['last_name']  ?? ''));

$fullCore = trim(($title !== '' ? $title.' ' : '') . trim($first.' '.$last));
$displayName = $user->isGuest
    ? 'Guest'
    : ($fullCore !== '' ? 'คุณ '.$fullCore : ($id->name ?? $claims['name'] ?? $id->username ?? 'User'));

/** ==============================
 *  ตำแหน่ง/บทบาท
 * ============================== */
$displayRole = $profile['academic_type_name']
    ?? $claims['academic_type_name']
    ?? $profile['employee_type_name']
    ?? $claims['employee_type_name']
    ?? $profile['category_type_name']
    ?? $claims['category_type_name']
    ?? null;

/** ==============================
 *  รูปโปรไฟล์
 * ============================== */
$authenBase = rtrim(Yii::$app->params['authenBase'] ?? 'https://sci-sskru.com/authen', '/');
$fallback   = Url::to('@web/template/berry/images/user/avatar-2.jpg');

$imgRaw = trim(
    (string)($profile['img'] ?? $claims['img'] ?? '')
);

$avatarUrl = $fallback;
if ($imgRaw !== '') {
    if (filter_var($imgRaw, FILTER_VALIDATE_URL)) {
        $avatarUrl = $imgRaw;
    } else {
        $avatarUrl = $authenBase . '/' . ltrim($imgRaw, '/');
    }
}

/* ✅ เพิ่ม cache-busting (v=timestamp) */
$cacheVer = $profile['updated_at'] ?? $claims['updated_at'] ?? '';
if ($cacheVer && $avatarUrl !== $fallback) {
    $avatarUrl .= (strpos($avatarUrl, '?') === false ? '?' : '&') . 'v=' . rawurlencode($cacheVer);
}

/** ==============================
 *  URL SSO และ Callback
 * ============================== */
$ssoLoginUrl  = Yii::$app->params['ssoLoginUrl'] ?? 'https://sci-sskru.com/hrm/login';
$callbackPath = Url::to(['/site/index']);
$greetIconHtml = Html::tag('i', '', [
    'class' => 'ti ti-user-circle me-2 align-text-bottom',
    'title' => 'ผู้ใช้',
    'aria-label' => 'ผู้ใช้',
]);
?>
<!-- Header -->
<header class="pc-header">
  <div class="header-wrapper">

    <!-- Left Menu -->
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

    <!-- Right User Profile -->
    <div class="ms-auto">
      <ul class="list-unstyled">
        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link head-link-primary dropdown-toggle arrow-none me-0"
             data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <?= Html::img($avatarUrl, [
                'alt'   => Html::encode($displayName),
                'class' => 'user-avtar rounded-circle border border-2 border-white shadow-sm',
                'style' => 'width:44px;height:44px;object-fit:cover;',
                'onerror' => "this.onerror=null;this.src='".Html::encode($fallback)."';",
                'title' => $displayName,
                'id'    => 'nav-avatar',
            ]) ?>
            <span><i class="ti ti-settings"></i></span>
          </a>

          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">

            <?php if ($user->isGuest): ?>
              <div class="dropdown-header">
                <h4 class="mb-1"><?= $greetIconHtml ?><span class="small text-muted">Guest</span></h4>
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
              <div class="dropdown-header">
                <h4 class="mb-1"><?= $greetIconHtml ?><span class="small text-muted"><?= Html::encode($displayName) ?></span></h4>
                <?php if (!empty($displayRole)): ?>
                  <div class="text-muted small"><?= Html::encode($displayRole) ?></div>
                <?php endif; ?>
                <hr class="my-2"/>
              </div>

              <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 280px)">
                <?= Html::a(
                      '<i class="ti ti-settings"></i><span> ข้อมูลส่วนตัว</span>',
                      'https://sci-sskru.com/hrm/edit-personal',
                      ['class'=>'dropdown-item','encode'=>false,'data-pjax'=>'0','target'=>'_blank','rel'=>'noopener noreferrer']
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
/* ========== 1) Auth JS (login/logout) ========== */
$jsAuth = <<<JS
(function authSesNavbar(){
  // Guest → Login
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

  // Logout
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
      // ให้ form POST logout ต่อไปตามปกติ
    });
  }
})();
JS;
$this->registerJs($jsAuth, \yii\web\View::POS_END);

/* ========== 2) Avatar from SSO ========== */
$authenBase = rtrim(Yii::$app->params['authenBase'] ?? 'https://sci-sskru.com/authen', '/');
$fallback   = \yii\helpers\Url::to('@web/template/berry/images/user/avatar-2.jpg');

$jsAvatar = <<<JS
(function updateSesAvatar(){
  // 1) พยายามอ่าน profile จากตัวแปร global ก่อน (เผื่อมี window.userProfile จาก AJAX)
  var profile = window.profile || window.userProfile || null;

  // 2) ถ้าไม่มี ให้ลองจาก localStorage
  if (!profile) {
    try {
      var ls = localStorage.getItem('userInfo');
      if (ls) {
        profile = JSON.parse(ls);
      }
    } catch (e) {
      profile = null;
    }
  }

  // 3) ถ้ายังไม่มีอีก ก็ไม่ต้องทำอะไร
  if (!profile || !profile.img) {
    return;
  }

  // 4) ประกอบ URL
  var base = '{$authenBase}';
  var raw  = (profile.img || '').trim();
  var full = '';
  if (/^https?:\\/\\//i.test(raw)) {
    full = raw;
  } else {
    full = base + '/' + raw.replace(/^\\/+/, '');
  }

  // 5) อัปเดตรูปบน navbar
  var avatar = document.getElementById('nav-avatar');
  if (avatar) {
    avatar.src = full;
    avatar.onerror = function(){
      this.onerror = null;
      this.src = '{$fallback}';
    };
  }
})();
JS;
$this->registerJs($jsAvatar, \yii\web\View::POS_END);
?>
