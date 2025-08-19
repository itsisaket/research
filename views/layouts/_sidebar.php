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
          <a class="pc-link" href="<?= Url::home() ?>">
            <span class="pc-micon"><i class="ti ti-dashboard"></i></span><span class="pc-mtext">หน้าหลัก</span></a
          >
        </li>

        <li class="pc-item pc-caption">
          <label>ข้อมูลการวิจัยคณะ</label>
          <i class="ti ti-apps"></i>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::home() ?>">
            <span class="pc-micon"><i class="ti ti-typography"></i></span>
            <span class="pc-mtext">นักวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::home() ?>">
            <span class="pc-micon"><i class="ti ti-color-swatch"></i></span>
            <span class="pc-mtext">งานวิจัย</span>
          </a>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?= Url::home() ?>">
            <span class="pc-micon"><i class="ti ti-plant-2"></i></span>
            <span class="pc-mtext">การตีพิมพ์เผยแพร่</span>
          </a>
        </li>

        <li class="pc-item pc-caption">
          <label>สำหรับนักวิจัย</label>
          <i class="ti ti-news"></i>
        </li>

        <?php if (Yii::$app->user->isGuest): ?>
          <li class="pc-item <?= $isActive('site/login') ?>">
            <?= Html::a(
              '<span class="pc-micon"><i class="ti ti-lock"></i></span><span class="pc-mtext">Login</span>',
              ['site/login'],
              ['class'=>'pc-link','encode'=>false,'data-pjax'=>'0', 'aria-current'=>$isActive('site/login')?'page':null]
            ) ?>
          </li>
        <?php else: ?>
          <li class="pc-item <?= $isActive('site/my-profile') ?>">
            <?= Html::a(
              '<span class="pc-micon"><i class="ti ti-user"></i></span><span class="pc-mtext">My Profile</span>',
              ['site/my-profile'],
              ['class'=>'pc-link','encode'=>false,'data-pjax'=>'0', 'aria-current'=>$isActive('site/my-profile')?'page':null]
            ) ?>
          </li>

          <!-- วิธีที่ 1: ใช้ฟอร์ม POST (ปลอดภัย ไม่พึ่ง JS) -->
          <li class="pc-item">
            <?php
              echo Html::beginForm(['site/logout'], 'post', ['class'=>'d-inline','data-pjax'=>'0']);
              echo Html::submitButton(
                '<span class="pc-micon"><i class="ti ti-logout"></i></span><span class="pc-mtext">Logout</span>',
                ['class'=>'pc-link btn btn-link p-0', 'encode'=>false]
              );
              echo Html::endForm();
            ?>
          </li>

          <?php /* วิธีที่ 2 (ทางเลือก): ใช้ลิงก์ + data-method ต้องมี yii.js
          <li class="pc-item">
            <?= Html::a(
              '<span class="pc-micon"><i class="ti ti-logout"></i></span><span class="pc-mtext">Logout</span>',
              ['site/logout'],
              ['class'=>'pc-link','encode'=>false,'data'=>['method'=>'post','pjax'=>0,'confirm'=>'ออกจากระบบใช่หรือไม่?']]
            ) ?>
          </li>
          */ ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>