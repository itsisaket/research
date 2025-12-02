<?php
/** @var yii\web\View $this */
/** @var bool $isGuest */
/** @var \app\models\User|null $u */
use yii\helpers\Url;

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

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const token = localStorage.getItem('hrm-sci-token');

    // 1) ถ้าไม่มี token → ไปหน้า report/index ทันที (ในฐานะ Guest)
    if (!token) {
        window.location.href = '<?= Url::to(['/report/index']) ?>';
        return;
    }

    // 2) กันส่ง token ซ้ำ (ป้องกัน loop)
    const SENT_FLAG = 'hrm-sci-sent-token';
    if (sessionStorage.getItem(SENT_FLAG) === '1') {
        return;
    }
    sessionStorage.setItem(SENT_FLAG, '1');

    // 3) มี token → สร้างฟอร์ม POST ส่งไปให้ /site/index
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= Url::to(['/site/index']) ?>';

    const inputToken = document.createElement('input');
    inputToken.type  = 'hidden';
    inputToken.name  = 'token';
    inputToken.value = token;
    form.appendChild(inputToken);

    document.body.appendChild(form);
    form.submit();
});
</script>


