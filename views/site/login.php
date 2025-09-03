<?php
use yii\helpers\Url;
$this->title = 'Login';
?>
<h1>Login</h1>

<div class="row">
  <div class="col-md-6">
    <form action="<?= Url::to(['/auth/password-login']) ?>" method="post" class="card card-body shadow-sm">
      <?= \yii\helpers\Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
      <div class="mb-3">
        <label class="form-label">ชื่อผู้ใช้ (uname)</label>
        <input name="uname" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">รหัสผ่าน</label>
        <input type="password" name="pwd" class="form-control" required>
      </div>
      <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
      <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="text-danger mt-2"><?= Yii::$app->session->getFlash('error') ?></div>
      <?php endif; ?>
    </form>
  </div>

  <div class="col-md-6">
    <div class="card card-body shadow-sm">
      <label class="form-label">ทดสอบด้วย JWT (วางโทเค็น)</label>
      <textarea id="jwt" class="form-control" rows="4" placeholder="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."></textarea>
      <button id="btn-jwt" class="btn btn-success mt-2 w-100">เข้าสู่ระบบด้วย JWT</button>
      <div id="msg" class="text-danger mt-2"></div>
    </div>
  </div>
</div>

<script>
(function(){
  const TOKEN_KEY = 'hrm-sci-token';

  // ย้าย token จากคุกกี้ชั่วคราว -> localStorage
  function getCookie(name){
    return document.cookie.split('; ').find(r=>r.startsWith(name+'='))?.split('=')[1];
  }
  const tok = getCookie('hrm-sci-token');
  if (tok) {
    localStorage.setItem(TOKEN_KEY, tok);
    // เคลียร์คุกกี้ให้สะอาด (ปล่อยให้หมดอายุเองก็ได้)
    document.cookie = 'hrm-sci-token=; Max-Age=0; path=/; SameSite=Lax';
  }

  // ปุ่ม login ด้วย JWT โดยตรง
  document.getElementById('btn-jwt').addEventListener('click', async function(){
    const t = document.getElementById('jwt').value.trim();
    if (!t) { document.getElementById('msg').textContent = 'กรุณาวาง JWT'; return; }
    localStorage.setItem(TOKEN_KEY, t);

    const res = await fetch('<?= Url::to(['/auth/jwt-login']) ?>', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer '+t},
      body: JSON.stringify({token: t})
    });
    const data = await res.json();
    if (data.ok) { window.location.href = '<?= Url::to(['/site/index']) ?>'; }
    else { document.getElementById('msg').textContent = 'ล็อกอินไม่ได้: ' + (data.error || 'UNKNOWN'); }
  });
})();
</script>
