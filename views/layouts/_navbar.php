<?php
use yii\helpers\Html;
use yii\helpers\Url;

$user = Yii::$app->user;
$id   = is_object($user->identity ?? null) ? $user->identity : null;

/* โปรไฟล์จาก identity (array เสมอ) */
$profile = is_array($id->profile ?? null) ? $id->profile : [];

/* ==== ชื่อที่จะแสดง (รวมคำนำหน้า) ==== */
$title = trim((string)($profile['title_name'] ?? ''));
$first = trim((string)($profile['first_name'] ?? ''));
$last  = trim((string)($profile['last_name'] ?? ''));
$full  = trim(($title !== '' ? $title.' ' : '').trim($first.' '.$last));
$displayName = $user->isGuest
  ? 'User Guest'
  : ($full !== '' ? 'คุณ '.$full : ((string)($id->name ?? $id->username ?? 'User')));

/* ==== บทบาท/ตำแหน่งย่อ ==== */
$displayRole = $profile['academic_type_name']
    ?? $profile['employee_type_name']
    ?? $profile['category_type_name']
    ?? null;

/* ==== รูปโปรไฟล์ + cache-busting ==== */
$authenBase = rtrim(Yii::$app->params['authenBase'] ?? 'https://sci-sskru.com/authen', '/');
$fallback   = Url::to('@web/template/berry/images/user/avatar-2.jpg');

$imgRaw     = isset($profile['img']) && is_string($profile['img']) ? trim($profile['img']) : '';
$avatarUrl  = $fallback;

if ($imgRaw !== '') {
    if (filter_var($imgRaw, FILTER_VALIDATE_URL)) {
        $sch = strtolower((string)parse_url($imgRaw, PHP_URL_SCHEME));
        if (in_array($sch, ['http','https'], true)) {
            $avatarUrl = $imgRaw;
        }
    } else {
        $avatarUrl = $authenBase . '/' . ltrim($imgRaw, '/');
    }
}
$avatarUrlFinal = $avatarUrl;
if ($avatarUrl !== $fallback) {
    $v = '';
    if (!empty($profile['updated_at'])) {
        $v = (string)$profile['updated_at'];
    } elseif (!empty($id->access_token)) {
        $parts = explode('.', $id->access_token);
        if (count($parts) >= 2) {
            $b64 = strtr($parts[1], '-_', '+/');
            $pad = strlen($b64) % 4; if ($pad) $b64 .= str_repeat('=', 4 - $pad);
            $bin = base64_decode($b64, true);
            if ($bin !== false) {
                $j = json_decode($bin, true);
                if (is_array($j)) $v = (string)($j['updated_at'] ?? $j['iat'] ?? $j['exp'] ?? '');
            }
        }
    }
    if ($v !== '') {
        $avatarUrlFinal .= (strpos($avatarUrlFinal, '?') === false ? '?' : '&') . 'v=' . rawurlencode($v);
    }
}

/* ไอคอนคงที่ */
$greetIconHtml = Html::tag('i', '', [
    'class' => 'ti ti-user-circle me-2 align-text-bottom',
    'title' => 'ผู้ใช้',
    'aria-label' => 'ผู้ใช้',
]);

/* URL SSO Login + callback กลับ index */
$ssoLoginUrl  = Yii::$app->params['ssoLoginUrl'] ?? 'https://sci-sskru.com/hrm/login';
$callbackPath = Url::to(['/site/index']); // กลับหน้า index (navbar จะรีเฟรชสถานะ)
?>
<!-- Header -->
<header class="pc-header">
  <div class="header-wrapper">
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

    <div class="ms-auto">
      <ul class="list-unstyled">
        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link head-link-primary dropdown-toggle arrow-none me-0"
             data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <?= Html::img($avatarUrlFinal, [
                'alt'   => Html::encode($displayName),
                'class' => 'user-avtar rounded-circle border border-2 border-white shadow-sm',
                'style' => 'width:44px;height:44px;object-fit:cover;object-position:top;',
                'onerror' => "this.onerror=null;this.src='".Html::encode($fallback)."';",
                'title' => $displayName,
                'id'    => 'nav-avatar',
            ]) ?>
            <span><i class="ti ti-settings"></i></span>
          </a>

          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
            <?php if ($user->isGuest): ?>
              <div class="dropdown-header">
                <h4 class="mb-1">
                  <?= $greetIconHtml ?>
                  <span class="small text-muted">Guest</span>
                </h4>
                <p class="text-muted mb-2">Please sign in</p>
                <hr class="my-2"/>
              </div>

              <?= Html::a(
                    '<i class="ti ti-lock"></i><span> Login</span>',
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
                <h4 class="mb-1">
                  <?= $greetIconHtml ?>
                  <span class="small text-muted" id="nav-display-name"><?= Html::encode($displayName) ?></span>
                </h4>
                <?php if (!empty($displayRole)): ?>
                  <div class="text-muted small" id="nav-role"><?= Html::encode($displayRole) ?></div>
                <?php else: ?>
                  <div class="text-muted small" id="nav-role" style="display:none"></div>
                <?php endif; ?>
                <hr class="my-2"/>
              </div>

              <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 280px)">
                <?= Html::a(
                      '<i class="ti ti-user"></i><span> My Profile</span>',
                      ['site/index'], /* เดิมชี้ site/login → เปลี่ยนเป็น index */
                      ['class' => 'dropdown-item', 'encode' => false, 'data-pjax' => '0']
                ) ?>
                <?= Html::a(
                      '<i class="ti ti-settings"></i><span> Account Settings</span>',
                      'https://sci-sskru.com/hrm/edit-personal',
                      ['class'=>'dropdown-item','encode'=>false,'data-pjax'=>'0','target'=>'_blank','rel'=>'noopener noreferrer']
                ) ?>

                <?php
                  echo Html::beginForm(['site/logout'], 'post', ['class' => 'm-0', 'data-pjax' => '0', 'id' => 'nav-logout-form']);
                  echo Html::submitButton(
                    '<i class="ti ti-logout"></i><span> Logout</span>',
                    ['class' => 'dropdown-item text-start', 'encode' => false, 'data-action' => 'logout', 'id' => 'nav-logout-btn']
                  );
                  echo Html::endForm();
                ?>
              </div>
            <?php endif; ?>
          </div>
        </li>
      </ul>
    </div>
  </div>
</header>

<?php
$js = <<<JS
(function(){
  // Guest -> Login: ส่งไป HRM พร้อม redirect กลับ /site/index
  var loginEl = document.getElementById('nav-login');
  if (loginEl) {
    loginEl.addEventListener('click', function(e){
      e.preventDefault();
      var sso = loginEl.getAttribute('data-sso-login') || 'https://sci-sskru.com/hrm/login';
      var cb  = loginEl.getAttribute('data-callback')  || '/site/index';
      var back = new URL(cb, window.location.origin).href;  // absolute callback
      var u = new URL(sso, window.location.href);
      if (!u.searchParams.has('redirect')) u.searchParams.set('redirect', back);
      location.href = u.toString();
    });
  }

  // Logout: เคลียร์ token ฝั่ง client ก่อน submit ฟอร์ม
  var logoutBtn  = document.getElementById('nav-logout-btn');
  var logoutForm = document.getElementById('nav-logout-form');
  if (logoutBtn && logoutForm) {
    logoutBtn.addEventListener('click', function(){
      try {
        localStorage.removeItem('hrm-sci-token');
        localStorage.removeItem('userInfo');
        localStorage.removeItem('accessToken');
        sessionStorage.clear();
        sessionStorage.setItem('did-logout', '1'); // กันลูป
      } catch (e) {}
      // แล้วปล่อยให้ form POST /site/logout ทำงาน
    });
  }
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
