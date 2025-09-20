<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
$this->title = 'Login';
$this->params['isLoginPage'] = true; // บอก layout ให้ข้ามตัวดัก

$csrf      = Yii::$app->request->getCsrfToken();
$csrfParam = Yii::$app->request->csrfParam;       // ✅ ใช้ชื่อพารามิเตอร์ CSRF แบบไดนามิก
$sync      = Url::to(['/site/my-profile']);       // POST: sync session จาก token
$logout    = Url::to(['/site/logout']);           // POST: Yii logout
$index     = Url::to(['/site/index']);            // กลับหน้าหลักเสมอ
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
  <meta charset="<?= Yii::$app->charset ?>">
  <?= Html::csrfMetaTags() ?>
  <title><?= Html::encode($this->title) ?></title>
  <meta http-equiv="refresh" content="10;url=<?= Html::encode($index) ?>"><!-- เผื่อ no-JS -->
  <style>
    /* ซ่อนทุกอย่าง เพื่อไม่ให้เห็นหน้า login เลย */
    html, body { background: #fff; }
    body { visibility: hidden; }
  </style>
</head>
<body>
<script>
const CSRF_TOKEN = <?= json_encode($csrf) ?>;
const CSRF_NAME  = <?= json_encode($csrfParam) ?>;   // ✅ ใช้ชื่อพารามิเตอร์จริงของระบบ
const SYNC_URL   = <?= json_encode($sync) ?>;
const LOGOUT_URL = <?= json_encode($logout) ?>;
const INDEX_URL  = <?= json_encode($index) ?>;

function postLogoutThenIndex(){
  try {
    if (sessionStorage.getItem('did-logout') === '1') {
      window.location.replace(INDEX_URL);
      return;
    }
    sessionStorage.setItem('did-logout','1');

    // ล้างด้าน client ให้หมดก่อน
    try {
      localStorage.removeItem('hrm-sci-token');
      localStorage.removeItem('userInfo');
      localStorage.removeItem('accessToken');
      sessionStorage.clear();
      sessionStorage.setItem('did-logout','1'); // set ซ้ำหลัง clear
    } catch(e){}

    // POST /site/logout (server จะทำลาย session)
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = LOGOUT_URL;

    const csrf = document.createElement('input');
    csrf.type  = 'hidden';
    csrf.name  = CSRF_NAME;          // ✅ ใช้ชื่อพารามิเตอร์จริง
    csrf.value = CSRF_TOKEN;
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();

    setTimeout(()=>window.location.replace(INDEX_URL), 1200);
  } catch(e){
    window.location.replace(INDEX_URL);
  }
}

function parseJwt(token){
  if (!token) return null;
  const parts = token.split('.');
  if (parts.length < 2) return null;
  try{
    let payload = parts[1].replace(/-/g,'+').replace(/_/g,'/');
    const pad = payload.length % 4; if (pad) payload += '='.repeat(4 - pad);
    return JSON.parse(atob(payload));
  }catch{ return null; }
}

(async function main(){
  try{
    let token = '';
    try {
      token = localStorage.getItem('hrm-sci-token') || '';  // ✅ กันกรณี storage โดนบล็อค
    } catch(e) {
      postLogoutThenIndex();
      return;
    }

    // ไม่มี token → ออกจากระบบทั้งหมด แล้วกลับ index
    if (!token.trim()) {
      postLogoutThenIndex();
      return;
    }

    // ตรวจ exp/leeway และต้องมี personal_id หรือ uname
    const claims = parseJwt(token) || {};
    const leeway = 120;
    const now    = Math.floor(Date.now()/1000);
    const exp    = Number.isFinite(claims.exp) ? claims.exp : null;
    const uid    = claims.personal_id || claims.uname || null;

    if ((exp !== null && (exp + leeway) < now) || !uid) {
      postLogoutThenIndex();
      return;
    }

    // มี token ใช้ได้ → sync สร้าง PHP session แล้วกลับ index
    const res = await fetch(SYNC_URL, {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN },
      body: JSON.stringify({ token })
    });

    const data = await res.json().catch(()=>({}));
    if (res.ok && data && data.ok) {
      window.location.replace(INDEX_URL);
      return;
    }

    // sync ไม่ผ่าน → ออกจากระบบทั้งหมด
    postLogoutThenIndex();

  } catch(e){
    postLogoutThenIndex();
  }
})();
</script>
</body>
</html>
