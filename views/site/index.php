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

    // 2) ถ้ามี token → ส่งไปให้ server เพื่อให้ PHP ตรวจสอบ JWT ผ่าน site/index
    // กัน loop: ส่งครั้งเดียวพอ ไม่วนซ้ำ
    if (sessionStorage.getItem('sent-token') === '1') {
        return;
    }
    sessionStorage.setItem('sent-token', '1');

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= Url::to(['/site/index']) ?>'; // ❗ เปลี่ยนจาก /site/login มาเป็น /site/index

    // token จาก HRM-SCI
    const inputToken = document.createElement('input');
    inputToken.type  = 'hidden';
    inputToken.name  = 'token';
    inputToken.value = token;
    form.appendChild(inputToken);

    // ⭐ ใส่ CSRF ไปด้วย (ถ้าระบบเปิดใช้งานอยู่)
    const csrfParam = '<?= Yii::$app->request->csrfParam ?>';
    const csrfToken = '<?= Yii::$app->request->getCsrfToken() ?>';

    const inputCsrf = document.createElement('input');
    inputCsrf.type  = 'hidden';
    inputCsrf.name  = csrfParam;
    inputCsrf.value = csrfToken;
    form.appendChild(inputCsrf);

    document.body.appendChild(form);
    form.submit();
});
</script>

