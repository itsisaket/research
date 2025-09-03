<?php
use yii\helpers\Url;
$this->title = 'Login';
?>
<h1>Login</h1>

<div id="login-area">
  <button id="btn-sso" class="btn btn-primary">เข้าสู่ระบบ SSO</button>
  <div id="msg" style="margin-top:1rem;"></div>
</div>

<script>
(function(){
  const TOKEN_KEY = 'hrm-sci-token';
  const token = localStorage.getItem(TOKEN_KEY);

  async function tryJwtLogin(tok) {
    const res = await fetch('<?= Url::to(['/auth/jwt-login']) ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + tok },
      body: JSON.stringify({ token: tok }) // เผื่อบาง proxy ตัด header
    });
    const data = await res.json();
    if (data.ok) {
      // เข้าระบบสำเร็จ
      window.location.href = '<?= Url::to(['/site/index']) ?>';
      return true;
    } else {
      document.getElementById('msg').textContent = 'ไม่สามารถเข้าสู่ระบบ: ' + (data.error || 'UNKNOWN');
      return false;
    }
  }

  // ถ้ามี token ใน localStorage ให้ลอง auto-login
  if (token) {
    tryJwtLogin(token);
  }

  // ถ้าไม่มี/หมดอายุ ให้เด้งไป SSO (หรือคลิกปุ่ม)
  document.getElementById('btn-sso').addEventListener('click', function(){
    // 👉 แนวทาง: redirect ไปหน้า SSO แล้วให้ SSO redirect กลับมาพร้อม token
    // จากนั้นหน้า callback ของเราเก็บ token ใส่ localStorage แล้วไปหน้า /site/login อีกครั้ง
    window.location.href = 'https://sci-sskru.com/authen/login?redirect=' + encodeURIComponent(window.location.origin + '/site/login');
  });
})();
</script>
