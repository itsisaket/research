<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

$controllerId = Yii::$app->controller->id;    // เช่น 'researchpro'
$route        = Yii::$app->controller->route; // เช่น 'researchpro/view'
$user = Yii::$app->user->identity ?? null;

$isCtrlActive = function(string $id) use ($controllerId) {
    return $controllerId === $id ? 'active' : '';
};

// เผื่อบางเมนูต้องเช็ก route เป๊ะ (กรณีพิเศษ)
$isRouteActive = function(string $r) use ($route) {
    return $route === $r ? 'active' : '';
};
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

    <li class="pc-item <?= $isRouteActive('site/index') ?>">
      <a class="pc-link" href="<?= Url::to(['/site/index']) ?>">
        <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
        <span class="pc-mtext">หน้าหลัก</span>
      </a>
    </li>

    <li class="pc-item pc-caption">
      <label>ข้อมูลการวิจัยคณะ</label>
      <i class="ti ti-apps"></i>
    </li>

    <li class="pc-item <?= $isCtrlActive('account') ?>">
      <a class="pc-link" href="<?= Url::to(['/account/index']) ?>">
        <span class="pc-micon"><i class="ti ti-typography"></i></span>
        <span class="pc-mtext">นักวิจัย</span>
      </a>
    </li>

    <li class="pc-item <?= $isCtrlActive('researchpro') ?>">
      <a class="pc-link" href="<?= Url::to(['/researchpro/index']) ?>">
        <span class="pc-micon"><i class="ti ti-color-swatch"></i></span>
        <span class="pc-mtext">งานวิจัย</span>
      </a>
    </li>

    <li class="pc-item <?= $isCtrlActive('article') ?>">
      <a class="pc-link" href="<?= Url::to(['/article/index']) ?>">
        <span class="pc-micon"><i class="ti ti-news"></i></span>
        <span class="pc-mtext">การตีพิมพ์เผยแพร่</span>
      </a>
    </li>

    <li class="pc-item <?= $isCtrlActive('utilization') ?>">
      <a class="pc-link" href="<?= Url::to(['/utilization/index']) ?>">
        <span class="pc-micon"><i class="ti ti-clipboard-check"></i></span>
        <span class="pc-mtext">นำไปใช้ประโยชน์</span>
      </a>
    </li>

    <!-- ✅ academic-service -->
    <li class="pc-item <?= $isCtrlActive('academic-service') ?>">
      <a class="pc-link" href="<?= Url::to(['/academic-service/index']) ?>">
        <span class="pc-micon"><i class="ti ti-plant-2"></i></span>
        <span class="pc-mtext">บริการวิชาการ</span>
      </a>
    </li>

  </ul>
</div>
</div>
</nav>
