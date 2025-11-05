<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;           // ✅ เพิ่มบรรทัดนี้
use app\models\User as UserModel;

/**
 * ==============================
 * 1) เตรียมข้อมูลผู้ใช้
 * ==============================
 */
$user = Yii::$app->user;
$id   = $user->identity ?? null;

// โปรไฟล์จาก identity หรือจาก JWT
$profile = is_array($id->profile ?? null) ? $id->profile : [];

// ✅ ดึง JWT claims (fallback)
$claims = [];
if ($id && property_exists($id, 'access_token') && is_string($id->access_token)) {
    $claims = UserModel::decodeJwtPayload($id->access_token) ?: [];
}

/**
 * ==============================
 * 2) กำหนดชื่อแสดงผล
 * ==============================
 */
$title = trim((string)($profile['title_name'] ?? $claims['title_name'] ?? ''));
$first = trim((string)($profile['first_name'] ?? $claims['first_name'] ?? ''));
$last  = trim((string)($profile['last_name']  ?? $claims['last_name']  ?? ''));

$fullCore = trim(($title !== '' ? $title . ' ' : '') . trim($first . ' ' . $last));

$displayName = $user->isGuest
    ? 'Guest'
    : ($fullCore !== '' ? 'คุณ ' . $fullCore : ($id->name ?? $claims['name'] ?? $id->username ?? 'User'));

/**
 * ==============================
 * 3) ตำแหน่ง/บทบาท
 * ==============================
 */
$displayRole = $profile['academic_type_name']
    ?? $claims['academic_type_name']
    ?? $profile['employee_type_name']
    ?? $claims['employee_type_name']
    ?? $profile['category_type_name']
    ?? $claims['category_type_name']
    ?? null;

/**
 * ==============================
 * 4) รูปโปรไฟล์
 *    - ถ้า login แล้วให้แสดงภาพจาก $authenBase
 * ==============================
 */

// ✅ เตรียมค่าเริ่มต้น
$authenBase = rtrim(Yii::$app->params['authenBase'] ?? 'https://sci-sskru.com/authen', '/') . '/';
$fallback   = Url::to('@web/template/berry/images/user/avatar-2.jpg', true);

// ✅ ตัวแปรผู้ใช้ (ไม่ต้องประกาศ $user ซ้ำ)
$identity = !$user->isGuest ? $user->identity : null;

// ✅ ดึงข้อมูลรูปจากโปรไฟล์หรือ JWT
$imgRaw = trim((string)($profile['img'] ?? $claims['img'] ?? ''));

// ✅ กำหนดรูปเริ่มต้นเป็น fallback
$avatarUrl = $fallback;

// ✅ ถ้ามีรูป
if ($imgRaw !== '') {
    if (filter_var($imgRaw, FILTER_VALIDATE_URL)) {
        // เป็น URL เต็มอยู่แล้ว
        $avatarUrl = $imgRaw;
    } else {
        // เป็น path/ชื่อไฟล์ → ต่อกับฐาน authenBase (กัน / ซ้อน)
        $avatarUrl = $authenBase . ltrim($imgRaw, '/');
    }
}

// ✅ เงื่อนไขเพิ่มเติม: ล็อกอินแล้วและ imgRaw ไม่ใช่ URL → ใช้ฐาน $authenBase แน่นอน
if ($identity && $imgRaw !== '' && !filter_var($imgRaw, FILTER_VALIDATE_URL)) {
    $avatarUrl = $authenBase . ltrim($imgRaw, '/');
}

// ✅ เพิ่ม cache-busting
$cacheVer = $profile['updated_at'] ?? $claims['updated_at'] ?? '';
if ($cacheVer && $avatarUrl !== $fallback) {
    $avatarUrl .= (strpos($avatarUrl, '?') === false ? '?' : '&') . 'v=' . rawurlencode($cacheVer);
}




/**
 * ==============================
 * 5) SSO / Callback
 * ==============================
 */
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
                'onerror' => "this.onerror=null;this.src='".Html::encode($fallback)."';",
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
                  <span class="small text-muted"><?= Html::encode($displayName) ?></span>
                </h4>
                <?php if (!empty($displayRole)): ?>
                  <div class="text-muted small"><?= Html::encode($displayRole) ?></div>
                <?php endif; ?>
                <hr class="my-2"/>
              </div>

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
/**
 * ==============================
 * 6) JS: login/logout
 * ==============================
 */
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

  // Logout → ล้างตัวแปรฝั่ง Browser
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
      // แล้วปล่อยให้ submit logout ตามปกติ
    });
  }
})();
JS;
$this->registerJs($jsAuth, \yii\web\View::POS_END);

/**
 * ==============================
 * 7) JS: อัปเดตรูปจากโปรไฟล์ที่มาทีหลัง (AJAX / localStorage)
 *    - ใช้ $authenBase และ $fallback จาก PHP
 * ==============================
 */
$jsAvatar = <<<JS
(function updateSesAvatar(){
  // 1) ลองอ่านจากตัวแปร global ก่อน
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

  // 3) ถ้าไม่มีโปรไฟล์หรือไม่มีรูป → ไม่ทำอะไร
  if (!profile || !profile.img) {
    return;
  }

  // 4) ประกอบ URL ให้ใช้ฐานเดียวกับ PHP (ฐานลงท้ายด้วย '/')
  var base = JSON.parse('{$this->context->view->renderPhpFile(
      null,
      [],
      function(){ echo Json::htmlEncode($authenBase); }
  )}');
  var fallback = JSON.parse('{$this->context->view->renderPhpFile(
      null,
      [],
      function(){ echo Json::htmlEncode($fallback); }
  )}');

  var raw  = String(profile.img || '').trim();
  var full = '';

  if (/^https?:\\/\\//i.test(raw)) {
    full = raw;
  } else {
    // base ลงท้ายด้วย '/', ตัด '/' นำหน้าของ raw ออก → กัน '//'
    full = base + raw.replace(/^\\/+/, '');
  }

  // 5) อัปเดตรูปบน navbar
  var avatar = document.getElementById('nav-avatar');
  if (avatar) {
    avatar.onerror = function(){
      this.onerror = null;
      this.src = fallback;
    };
    avatar.src = full;
  }
})();
JS;
$this->registerJs($jsAvatar, \yii\web\View::POS_END);
?>
