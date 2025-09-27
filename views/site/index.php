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
        คุณกำลังเข้าระบบในนาม <strong>Guest</strong>
      </div>
      <!-- ตามที่ต้องการ: ไม่เด้งไปหน้า login -->
    <?php else: ?>
      <div class="alert alert-success">
        ยินดีต้อนรับ
        <strong><?= Html::encode($u->name ?: $u->username ?: $u->id) ?></strong>
      </div>

      <div class="card shadow-sm" style="max-width:720px">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-muted small">อีเมล</div>
              <div class="fw-semibold">
                <?= Html::encode($u->email ?: ($u->profile['email'] ?? '-')) ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="text-muted small">หน่วยงาน</div>
              <div class="fw-semibold">
                <?= Html::encode($u->profile['dept_name'] ?? '-') ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>
