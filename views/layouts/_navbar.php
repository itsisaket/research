<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var \yii\web\User $user */
$user = Yii::$app->user;
$identity = $user->identity ?? null;

/* Greeting */
$tz = new \DateTimeZone('Asia/Bangkok');
$h  = (int) (new \DateTime('now', $tz))->format('G');
$greet = $h < 12 ? 'Good Morning' : ($h < 18 ? 'Good Afternoon' : 'Good Evening');

/* โปรไฟล์จาก identity (คาดว่าเป็น array เสมอจากโมเดล User ใหม่) */
$profileRaw = $identity->profile ?? null;
$profile    = is_array($profileRaw) ? $profileRaw : [];

/* ชื่อแสดงผล (fallback ตามลำดับ) */
$first = trim((string)($profile['first_name'] ?? ''));
$last  = trim((string)($profile['last_name'] ?? ''));
$baseName = trim($first.' '.$last);
if ($baseName !== '') {
    $displayName = 'คุณ '.$baseName;
} else {
    $displayName = $identity ? ((string)($identity->name ?? $identity->username ?? 'Guest')) : 'Guest';
}

/* บทบาท/ตำแหน่ง */
$displayRole = $profile['academic_type_name']
    ?? $profile['employee_type_name']
    ?? $profile['category_type_name']
    ?? null;

/* รูปโปรไฟล์ */
$imgPathRaw = $profile['img'] ?? null;             // "/uploads/5.jpg" หรือ URL
$imgPath    = is_string($imgPathRaw) ? trim($imgPathRaw) : null;
$authenBase = 'https://sci-sskru.com/authen';      // โดเมนของไฟล์รูปจาก SSO
$fallback   = Url::to('@web/template/berry/images/user/avatar-2.jpg');
$avatarUrl  = $fallback;

if ($imgPath) {
    if (filter_var($imgPath, FILTER_VALIDATE_URL)) {
        $scheme = parse_url($imgPath, PHP_URL_SCHEME);
        if (in_array(strtolower((string)$scheme), ['http', 'https'], true)) {
            $avatarUrl = $imgPath;
        }
    } else {
        $avatarUrl = rtrim($authenBase, '/') . '/' . ltrim($imgPath, '/');
    }
}

/* cache buster (อย่าใส่กับ fallback) */
$avatarUrlFinal = $avatarUrl;
if ($avatarUrl !== $fallback) {
    $v = '';
    if (!empty($profile['updated_at'])) {
        $v = (string)$profile['updated_at'];
    } elseif (is_object($identity) && property_exists($identity, 'iat') && isset($identity->iat)) {
        $v = (string)(int)$identity->iat;
    }
    if ($v !== '') {
        $avatarUrlFinal .= (strpos($avatarUrlFinal, '?') === false ? '?' : '&') . 'v=' . rawurlencode($v);
    }
}
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
                  'id'    => 'nav-avatar', // ให้ JS อัปเดตภายหลังได้
              ]) ?>
            <span><i class="ti ti-settings"></i></span>
          </a>

          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
            <?php if ($user->isGuest): ?>
              <div class="dropdown-header">
                <h4><?= Html::encode($greet) ?>, <span class="small text-muted">Guest</span></h4>
                <p class="text-muted mb-2">Please sign in</p>
                <hr />
              </div>
              <?= Html::a(
                    '<i class="ti ti-lock"></i><span> Login</span>',
                    ['site/login'],
                    ['class' => 'dropdown-item', 'encode' => false, 'data-pjax' => '0']
              ) ?>
            <?php else: ?>
              <div class="dropdown-header">
                <h4>
                  <span id="nav-greet"><?= Html::encode($greet) ?></span>,
                  <span class="small text-muted" id="nav-display-name"><?= Html::encode($displayName) ?></span>
                </h4>
                <?php if (!empty($displayRole)): ?>
                  <div class="text-muted small" id="nav-role"><?= Html::encode($displayRole) ?></div>
                <?php else: ?>
                  <div class="text-muted small" id="nav-role" style="display:none"></div>
                <?php endif; ?>
                <hr />
              </div>

              <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 280px)">
                <?= Html::a(
                      '<i class="ti ti-user"></i><span> My Profile</span>',
                      ['site/my-profile'],
                      ['class' => 'dropdown-item', 'encode' => false, 'data-pjax' => '0']
                ) ?>
                <?= Html::a(
                      '<i class="ti ti-settings"></i><span> Account Settings</span>',
                      'https://sci-sskru.com/hrm/edit-personal',
                      ['class'=>'dropdown-item','encode'=>false,'data-pjax'=>'0','target'=>'_blank','rel'=>'noopener noreferrer']
                ) ?>

                <?php
                  // Logout ใช้ POST (ปลอดภัย) + data-action เพื่อลบ localStorage ด้วย JS
                  echo Html::beginForm(['site/logout'], 'post', ['class' => 'm-0', 'data-pjax' => '0']);
                  echo Html::submitButton(
                    '<i class="ti ti-logout"></i><span> Logout</span>',
                    ['class' => 'dropdown-item text-start', 'encode' => false, 'data-action' => 'logout']
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
// === Lazy profile/Avatar update ทางฝั่ง client ถ้า identity->profile ยังว่าง ===
$authProfileUrl = Url::to(['/auth/profile'], true);
$defaultAvatar  = $fallback;
$authenBaseJs   = $authenBase;
$myProfileUrl   = Url::to(['/site/my-profile'], true);
$js = <<<JS
(function(){
  const TOKEN_KEY = 'hrm-sci-token';
  const tok = localStorage.getItem(TOKEN_KEY);
  if (!tok) return;

  // ถ้าใน server-render ยังไม่มีชื่อจริง ลองดึงโปรไฟล์แล้วอัปเดต UI
  const hasServerName = document.getElementById('nav-display-name')?.textContent.trim() !== '' &&
                        !document.getElementById('nav-display-name')?.textContent.includes('Guest');

  if (!hasServerName) {
    // decode payload เพื่อได้ personal_id (fallback ใช้ uname)
    function decodePayload(jwt){
      try{
        const p = jwt.split('.')[1];
        return JSON.parse(atob(p.replace(/-/g,'+').replace(/_/g,'/')));
      }catch(e){ return null; }
    }
    const claims = decodePayload(tok) || {};
    const personalId = claims.personal_id || claims.uname || '';

    if (!personalId) return;

    fetch('$authProfileUrl', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ token: tok, personal_id: personalId })
    })
    .then(r => r.json())
    .then(d => {
      if (!d || !d.ok) throw new Error(d?.error || 'PROFILE_FAIL');
      const p = d.profile || {};

      // ชื่อ-บทบาท
      const name = [p.title_name, p.first_name, p.last_name].filter(Boolean).join(' ').trim();
      const role = p.academic_type_name || p.employee_type_name || p.category_type_name || '';
      const nameEl = document.getElementById('nav-display-name');
      const roleEl = document.getElementById('nav-role');
      if (name && nameEl) nameEl.textContent = 'คุณ ' + name;
      if (roleEl) {
        if (role) { roleEl.style.display=''; roleEl.textContent = role; }
        else { roleEl.style.display='none'; roleEl.textContent = ''; }
      }

      // รูปโปรไฟล์
      const imgRaw = p.img || '';
      let imgUrl = '$defaultAvatar';
      if (imgRaw) {
        if (/^https?:\\/\\//i.test(imgRaw)) {
          imgUrl = imgRaw;
        } else {
          imgUrl = ('$authenBaseJs'.replace(/\\/+$/,'') + '/' + String(imgRaw).replace(/^\\/+/, ''));
        }
      }
      const v = p.updated_at ? String(p.updated_at) : (claims.iat ? String(parseInt(claims.iat)) : '');
      if (imgUrl !== '$defaultAvatar' && v) {
        imgUrl += (imgUrl.includes('?') ? '&' : '?') + 'v=' + encodeURIComponent(v);
      }
      const avatar = document.getElementById('nav-avatar');
      if (avatar) { avatar.src = imgUrl; }
    })
    .catch(()=>{
      // โทเค็นไม่ใช้การได้ → ไม่รบกวน UI, ให้ผู้ใช้ทำงานต่อหรือ re-login เอง
    });
  }

  // ลบ token ตอน logout (ปุ่ม submit ใน dropdown)
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-action="logout"]');
    if (btn) {
      try { localStorage.removeItem(TOKEN_KEY); } catch(e){}
    }
  });
})();
JS;
$this->registerJs($js);
