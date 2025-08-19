<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use app\assets\BerryAsset;
BerryAsset::register($this);

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
  <div class="loader-track">
    <div class="loader-fill"></div>
  </div>
</div>

<!-- Sidebar -->
<nav class="pc-sidebar">
<div class="navbar-wrapper">
<div class="m-header">

<?= Html::img('@web/template/berry/images/logo-dark.svg', ['class' => 'icon-image']) ?>
</div>
    <div class="navbar-content">
      <ul class="pc-navbar">
        <li class="pc-item pc-caption">
          <label>Dashboard</label>
          <i class="ti ti-dashboard"></i>
        </li>
        <li class="pc-item">
          <a href="./site/index" class="pc-link"
            ><span class="pc-micon"><i class="ti ti-dashboard"></i></span><span class="pc-mtext">หน้าหลัก</span></a
          >
        </li>

        <li class="pc-item pc-caption">
          <label>ข้อมูลการวิจัยคณะ</label>
          <i class="ti ti-apps"></i>
        </li>
        <li class="pc-item">
          <a href="../elements/bc_typography.html" class="pc-link">
            <span class="pc-micon"><i class="ti ti-typography"></i></span>
            <span class="pc-mtext">นักวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a href="../elements/bc_color.html" class="pc-link">
            <span class="pc-micon"><i class="ti ti-color-swatch"></i></span>
            <span class="pc-mtext">งานวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a href="../elements/icon-tabler.html" class="pc-link">
            <span class="pc-micon"><i class="ti ti-plant-2"></i></span>
            <span class="pc-mtext">การตีพิมพ์เผยแพร่</span>
          </a>
        </li>
        <li class="pc-item pc-caption">
          <label>สำหรับนักวิจัย</label>
          <i class="ti ti-news"></i>
        </li>
        <li class="pc-item">
          <a class="pc-link" target="_blank" href="./site/login">
            <span class="pc-micon"><i class="ti ti-lock"></i></span>
            <span class="pc-mtext">Login</span>
          </a>
        </li>
        <li class="pc-item">
          <a href="../pages/register-v3.html" target="_blank" class="pc-link">
            <span class="pc-micon"><i class="ti ti-user-plus"></i></span>
            <span class="pc-mtext">Register</span>
          </a>
        </li>

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
    <a
      class="pc-head-link head-link-primary dropdown-toggle arrow-none me-0"
      data-bs-toggle="dropdown"
      href="#"
      role="button"
      aria-haspopup="false"
      aria-expanded="false"
    >
      <img src="<?= Url::to('@web/template/berry/images/user/avatar-2.jpg') ?>" alt="user-image" class="user-avatar" />
      <span>
        <i class="ti ti-settings"></i>
      </span>
    </a>
    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
      <div class="dropdown-header">
        <h4>
          Good Morning,
          <span class="small text-muted">John Doe</span>
        </h4>
        <p class="text-muted">Project Admin</p>
        <hr />
        <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 280px)">

          <a href="#" class="dropdown-item">
            <i class="ti ti-settings"></i>
            <span>Account Settings</span>
          </a>
          <a href="#" class="dropdown-item">
            <i class="ti ti-user"></i>
            <span>Social Profile</span>
          </a>
          <a href="#" class="dropdown-item">
            <i class="ti ti-logout"></i>
            <span>Logout</span>
          </a>
        </div>
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
    <?= $content ?> <!-- ใส่เนื้อหาในหน้านี้ -->
  </div>
</div>

<!-- Footer -->
<footer class="pc-footer">
<div class="footer-wrapper container-fluid">
        <div class="row">
          <div class="col-sm-6 my-1">
            <p class="m-0">
              Berry &#9829; crafted by Team
              <a href="https://themeforest.net/user/codedthemes" target="_blank">CodedThemes</a>
            </p>
          </div>
          <div class="col-sm-6 ms-auto my-1">
            <ul class="list-inline footer-link mb-0 justify-content-sm-end d-flex">
              <li class="list-inline-item"><a href="../index.html">Home</a></li>
              <li class="list-inline-item"><a href="https://codedthemes.gitbook.io/berry-bootstrap/" target="_blank">Documentation</a></li>
              <li class="list-inline-item"><a href="https://codedthemes.support-hub.io/" target="_blank">Support</a></li>
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
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
