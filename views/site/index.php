<?php
/** @var yii\web\View $this */
/** @var bool $isGuest */
/** @var \app\models\User|null $u */

use yii\helpers\Url;
use yii\helpers\Html;
use miloschuman\highcharts\HighchartsAsset;

HighchartsAsset::register($this)->withScripts(['modules/exporting']);

$this->title = 'หน้าหลัก';
?>

<div class="site-index">

  <!-- Page header (Berry style) -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center gap-3">
        <div>
          <h1 class="h3 mb-1 text-primary">ระบบสารสนเทศงานวิจัย เพื่อการบริหารจัดการ</h1>
          <div class="text-muted">LASC SSKRU Research Management</div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const token = localStorage.getItem('hrm-sci-token');

    // 1) ถ้าไม่มี token → ไปหน้า report/index ในฐานะ Guest
    if (!token) {
        window.location.href = '<?= Url::to(['/report/index']) ?>';
        return;
    }

    // 2) ถ้ามี token → ส่งต่อไปหน้า login ให้จัดการ sync + redirect เอง
    //    (หน้า login ของคุณตอนนี้มี JS ที่เรียก API_PROFILE_URL + SYNC_URL อยู่แล้ว)
    const redirect = encodeURIComponent('<?= Url::to(['/report/index']) ?>');
    window.location.href = '<?= Url::to(['/site/login']) ?>?redirect=' + redirect;
});
</script>
