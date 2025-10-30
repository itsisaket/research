<?php
/** @var yii\web\View $this */
/** @var bool $isGuest */
/** @var \app\models\User|null $u */

use yii\helpers\Html;
// ใช้ Highcharts เมื่อจำเป็นเท่านั้น; ถ้าไม่ใช้ ก็ตัด 2 บรรทัดถัดไปทิ้งได้
use miloschuman\highcharts\HighchartsAsset;
HighchartsAsset::register($this)->withScripts(['modules/exporting']);

$this->title = 'หน้าหลัก';
?>

<div class="site-index">

  <!-- Page header (Berry style) -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center gap-3">
        <!-- รูปโลโก้/องค์กร ถ้าต้องการ: Html::img('@web/img/'.$model->org_id.'.png', ['style'=>'height:64px']) -->
        <div>
          <h1 class="h3 mb-1 text-primary">ระบบสารสนเทศงานวิจัย เพื่อการบริหารจัดการ</h1>
          <div class="text-muted">LASC SSKRU Research Management</div>
        </div>
      </div>
    </div>
  </div>

  <!-- แสดงผลตามสถานะ Guest/User -->
  <div class="container py-4 px-0">
<?php if ($isGuest): ?>
  <div class="alert alert-warning">
    กรุณาเข้าสู่ระบบ
  </div>
<?php else: ?>
  <div class="card p-3">
    <h5>ยินดีต้อนรับ, <?= Html::encode($displayName) ?></h5>
    <p>Email: <?= Html::encode($displayEmail) ?></p>
    <p>Username: <?= Html::encode($u->username ?? '-') ?></p>
  </div>
<?php endif; ?>
  </div>

</div>
