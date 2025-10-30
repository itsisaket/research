<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\User as UserModel;

/** ถ้าถูก include โดยส่งตัวแปรมาด้วย ให้ใช้ตัวนั้นก่อน */
if (!isset($fullName) || $fullName === '') {
    $user = Yii::$app->user;
    $id   = $user->identity ?? null;

    $profile = is_array($id->profile ?? null) ? $id->profile : [];

    $claims = [];
    if ($id && property_exists($id, 'access_token') && is_string($id->access_token)) {
        $claims = UserModel::decodeJwtPayload($id->access_token) ?: [];
    }

    $title = trim((string)($profile['title_name'] ?? $claims['title_name'] ?? ''));
    $first = trim((string)($profile['first_name'] ?? $claims['first_name'] ?? ''));
    $last  = trim((string)($profile['last_name']  ?? $claims['last_name']  ?? ''));

    $fullName = trim(($title ? $title.' ' : '') . trim($first.' '.$last));
    $avatar   = $profile['avatar'] ?? $claims['avatar'] ?? null;
}

// url logout ของ Yii2
$logoutUrl = Url::to(['/site/logout']);
?>

<nav class="pc-navbar">
  <div class="navbar-content">
    <div class="d-flex align-items-center gap-2">
      <button class="pc-head-link head-link-secondary" id="sidebar-toggle">
        <i class="ti ti-menu-2"></i>
      </button>
      <span class="fw-bold d-none d-md-inline">LASC SSKRU 2025</span>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">

      <div class="dropdown">
        <a class="d-flex align-items-center gap-2 text-decoration-none" href="#" data-bs-toggle="dropdown">
          <?php if (!empty($avatar)): ?>
            <img src="<?= Html::encode($avatar) ?>" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
          <?php else: ?>
            <span class="avatar bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center" style="width:32px;height:32px;">
              <i class="ti ti-user"></i>
            </span>
          <?php endif; ?>
          <span class="d-none d-sm-inline"><?= Html::encode($fullName ?: 'ผู้ใช้งาน') ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= Url::to(['/site/profile']) ?>"><i class="ti ti-id mr-2"></i> โปรไฟล์ของฉัน</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <?= Html::beginForm($logoutUrl, 'post', ['id' => 'logout-form']) ?>
            <?= Html::submitButton('<i class="ti ti-logout"></i> ออกจากระบบ', [
                'class' => 'dropdown-item text-danger',
                'id'    => 'btn-logout'
            ]) ?>
            <?= Html::endForm() ?>
          </li>
        </ul>
      </div>

    </div>
  </div>
</nav>

<?php
$js = <<<JS
(function() {
  const btn = document.getElementById('btn-logout');
  if (!btn) return;
  btn.addEventListener('click', function(){
    try {
      localStorage.removeItem('hrm-sci-token');
      localStorage.removeItem('userInfo');
      localStorage.removeItem('accessToken');
      sessionStorage.clear();
    } catch(e) {}
  });
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
