<?php
/** @var yii\web\View $this */
$this->title = 'Login';
?>
<h3>กำลังตรวจสอบสิทธิ์...</h3>
<script>
const token = localStorage.getItem('hrm-sci-token');

function parseJwt(t){
  try{
    const p = t.split('.')[1].replace(/-/g,'+').replace(/_/g,'/');
    const pad = p.length % 4; const s = pad ? p + '='.repeat(4-pad) : p;
    const json = atob(s);
    return JSON.parse(decodeURIComponent(Array.from(json).map(c=>'%'+c.charCodeAt(0).toString(16).padStart(2,'0')).join('')));
  }catch(e){ return null; }
}

function goHrmLogin(){
//const back = encodeURIComponent(location.origin + '/site/login');
//  location.href = 'https://sci-sskru.com/hrm/login?redirect=' + back;
location.href = 'https://sci-sskru.com/hrm/login';
}

(async ()=>{
  if (!token) { goHrmLogin(); return; }

  const payload = parseJwt(token) || {};
  const now = Math.floor(Date.now()/1000);
  if (!payload.personal_id || (payload.exp && payload.exp < now)) {
    localStorage.removeItem('hrm-sci-token');
    goHrmLogin(); return;
  }

  try{
    const res = await fetch('/site/login-bind', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ token, personal_id: payload.personal_id }).toString()
    });
    const j = await res.json();
    if (j.ok) location.href = '/site/index';
    else { alert('Login failed: ' + (j.error || 'unknown')); localStorage.removeItem('hrm-sci-token'); goHrmLogin(); }
  }catch(e){
    alert('Network error'); goHrmLogin();
  }
})();
</script>
