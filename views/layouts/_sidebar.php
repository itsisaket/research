<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

$isActive = function(string $route) {
    return Yii::$app->controller->route === $route ? 'active' : '';
};
$user = Yii::$app->user->identity ?? null;
?>


<nav class="pc-sidebar">
<div class="navbar-wrapper">
<div class="m-header">

<?= Html::img('@web/template/berry/images/lasc_logo_ris.png', ['class' => 'icon-image', 'width' => '150', 'height' => 'auto']) ?>
</div>
    <div class="navbar-content">
      <ul class="pc-navbar">
        <li class="pc-item pc-caption">
          <label>Dashboard</label>
          <i class="ti ti-dashboard"></i>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::to(['/site/index']) ?>">
            <span class="pc-micon"><i class="ti ti-dashboard"></i></span><span class="pc-mtext">หน้าหลัก</span></a
          >
        </li>

        <li class="pc-item pc-caption">
          <label>ข้อมูลการวิจัยคณะ</label>
          <i class="ti ti-apps"></i>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::to(['/account']) ?>">
            <span class="pc-micon"><i class="ti ti-typography"></i></span>
            <span class="pc-mtext">นักวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::to(['/researchpro']) ?>">
            <span class="pc-micon"><i class="ti ti-color-swatch"></i></span>
            <span class="pc-mtext">งานวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::to(['/article']) ?>">
            <span class="pc-micon"><i class="ti ti-plant-2"></i></span>
            <span class="pc-mtext">การตีพิมพ์เผยแพร่</span>
          </a>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::to(['/utilization']) ?>">
            <span class="pc-micon"><i class="ti ti-plant-2"></i></span>
            <span class="pc-mtext">นำไปใช้ประโยชน์</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>