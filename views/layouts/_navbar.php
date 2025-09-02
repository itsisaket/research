<?php
use yii\helpers\Html;
use yii\helpers\Url;

$user = Yii::$app->user;
$id   = $user->identity ?? null;

/* Greeting */
$tz = new \DateTimeZone('Asia/Bangkok');
$h  = (int) (new \DateTime('now', $tz))->format('G');
$greet = $h < 12 ? 'Good Morning' : ($h < 18 ? 'Good Afternoon' : 'Good Evening');

/* ดึง profile (บังคับเป็น array) */
$sessionIdentityRaw = Yii::$app->session->get('identity');
$sessionIdentity = is_array($sessionIdentityRaw) ? $sessionIdentityRaw : [];
$profileRaw = $sessionIdentity['profile'] ?? ($id->profile ?? null);
$profile    = is_array($profileRaw) ? $profileRaw : [];

/* ถ้า profile ยังไม่ใช่อาร์เรย์ และผู้ใช้ล็อกอิน → ลองโหลดจาก API หนึ่งครั้ง */
if (empty($profile) && !$user->isGuest) {
    $personalId = null;

    if (is_string($id->profile ?? null) && preg_match('/^\d{10,13}$/', $id->profile)) {
        $personalId = $id->profile;
    } else {
        $personalId = (string)($id->id ?? $id->username ?? '');
    }

    if ($personalId !== '') {
        try {
            $fetched = Yii::$app->apiAuth->getProfileByPersonalId($personalId);
            if (is_array($fetched)) {
                $profile = $fetched;
                // อัปเดต session identity ให้ส่วนอื่นใช้ได้
                $sessionIdentity['profile'] = $profile;
                Yii::$app->session->set('identity', $sessionIdentity);

                // ถ้าต้องการ sync กลับเข้า identity object (เช็กให้ชัวร์ว่าเป็น object)
                if (is_object($id) && property_exists($id, 'profile')) {
                    $id->profile = $profile;
                }
            }
        } catch (\Throwable $e) {
            Yii::warning('Header lazy profile fetch failed: '.$e->getMessage(), 'ui.profile');
        }
    }
}

/* ชื่อที่จะแสดง */
$first = trim((string)($profile['first_name'] ?? ''));
$last  = trim((string)($profile['last_name'] ?? ''));
$baseName = trim($first.' '.$last);
if ($baseName !== '') {
    $displayName = 'คุณ '.$baseName;
} else {
    $displayName = $id ? ((string)($id->name ?? $id->username ?? 'Guest')) : 'Guest';
}

/* บทบาท/ตำแหน่งย่อ */
$displayRole = $profile['academic_type_name']
    ?? $profile['employee_type_name']
    ?? $profile['category_type_name']
    ?? null;

/* รูปโปรไฟล์ */
$imgPathRaw = $profile['img'] ?? null;          // "/uploads/5.jpg" หรือ URL
$imgPath    = is_string($imgPathRaw) ? trim($imgPathRaw) : null;
$authenBase = 'https://sci-sskru.com/authen';
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
    } elseif (is_object($id) && property_exists($id, 'iat') && isset($id->iat)) {
        $v = (string)(int)$id->iat;
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
                  <?= Html::encode($greet) ?>,
                  <span class="small text-muted"><?= Html::encode($displayName) ?></span>
                </h4>
                <?php if (!empty($displayRole)): ?>
                  <div class="text-muted small"><?= Html::encode($displayRole) ?></div>
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
                  // Logout ใช้ POST (ปลอดภัย ไม่พึ่ง JS)
                  echo Html::beginForm(['site/logout'], 'post', ['class' => 'm-0', 'data-pjax' => '0']);
                  echo Html::submitButton(
                    '<i class="ti ti-logout"></i><span> Logout</span>',
                    ['class' => 'dropdown-item text-start', 'encode' => false]
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
