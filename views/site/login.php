<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
$this->title = 'Login: ';
$this->params['breadcrumbs'][] = $this->title;

$csrf   = Yii::$app->request->getCsrfToken();
$myUrl  = Url::to(['/site/my-profile']); // POST: sync identity into PHP session
$logout = Url::to(['/site/logout']);     // POST: Yii logout
?>
<style>
/* ===== Profile Card (clean + responsive) ===== */
.profile-card { max-width: 860px; border: 1px solid #e5e7eb; border-radius: 1rem; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,.06); }
.profile-cover {
  height: 140px;
  background: radial-gradient(1200px 400px at -10% -50%, #a78bfa 10%, transparent 60%),
              radial-gradient(900px 400px at 110% -40%, #60a5fa 10%, transparent 60%),
              linear-gradient(135deg, #7c3aed, #2563eb);
}
.profile-body { padding: 1.25rem 1.25rem 1.5rem 1.25rem; }
.profile-avatar-wrap { position: relative; margin-top: -56px; padding-left: 150px; min-height: 96px; }
.profile-avatar {
  position: absolute; left: 16px; top: -48px;
  width: 112px; height: 112px; border-radius: 999px;
  object-fit: cover; border: 4px solid #fff; box-shadow: 0 6px 18px rgba(0,0,0,.15);
  background: #f3f4f6;
}
.profile-name { font-size: 1.25rem; font-weight: 700; }
.profile-email { color: #6b7280; font-size: .95rem; }
.info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: .75rem; }
@media (max-width: 576px){ .info-grid{ grid-template-columns: 1fr; } .profile-avatar-wrap{ padding-left: 136px; } }
.info-item { background: #fafafa; border: 1px solid #f0f1f3; border-radius: .75rem; padding: .6rem .75rem; }
.info-item .lbl { display:block; font-size: .75rem; color:#6b7280; letter-spacing:.2px; }
.info-item .val { font-weight:600; margin-top:.15rem; }
.badge-pill { display:inline-block; border-radius:999px; padding:.25rem .6rem; font-size:.75rem; font-weight:600; }
.badge-green { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
.badge-gray  { background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
.sr-actions { gap:.5rem; }
.skeleton { background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 37%, #f3f4f6 63%); animation: shimmer 1.2s infinite; background-size: 400% 100%; }
@keyframes shimmer { 0%{background-position: 100% 0} 100%{background-position: 0 0} }
</style>

<div class="container py-4">
  <h1><?= Html::encode($this->title) ?></h1>
  <p class="text-muted">
    หน้านี้จะตรวจสอบโทเคน (คีย์: <code>hrm-sci-token</code>) ถ้ามีจะดึงโปรไฟล์จาก HRM แล้วแสดงผลทันที
  </p>

  <!-- สถานะ -->
  <div id="status" class="alert alert-info">กำลังตรวจสอบ...</div>
<!-- สรุป personal_id -->
  <div class="mt-2">
    <span class="badge-pill badge-gray">personal_id: <span id="pid">ยังไม่มีข้อมูล</span></span>
  </div>

  <!-- การ์ดโปรไฟล์ -->
  <div id="profile-card" class="profile-card mt-3 d-none">
    <div class="profile-cover"></div>
    <div class="profile-body">
      <div class="profile-avatar-wrap">
        <img id="avatar" class="profile-avatar skeleton" alt="avatar">
        <div class="profile-name" id="fullName"> </div>
        <div class="profile-email" id="email"> </div>
      </div>

      <div class="info-grid">
        <div class="info-item">
          <span class="lbl">หน่วยงาน</span>
          <span class="val" id="dept_name"> </span>
        </div>
        <div class="info-item">
          <span class="lbl">สายงาน</span>
          <span class="val" id="category_type_name"> </span>
        </div>
        <div class="info-item">
          <span class="lbl">ประเภทพนักงาน</span>
          <span class="val" id="employee_type_name"> </span>
        </div>
        <div class="info-item">
          <span class="lbl">ตำแหน่งวิชาการ</span>
          <span class="val" id="academic_type_name"> </span>
        </div>
      </div>
    </div>
  </div>

  <!-- ปุ่ม -->
  <div class="mt-3 d-flex flex-wrap sr-actions">
    <a id="btn-auth" href="#" class="btn btn-primary btn-sm">กำลังโหลด...</a>
    <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-outline-secondary btn-sm">กลับหน้าแรก</a>
  </div>
</div>

<script>
const CSRF_TOKEN = '<?= $csrf ?>';
const MY_PROFILE = '<?= $myUrl ?>';
const LOGOUT_URL = '<?= $logout ?>';

// ---- helpers: decode JWT (base64url) ----
function b64urlDecode(str){
  try {
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    const pad = str.length % 4; if (pad) str += '='.repeat(4 - pad);
    const bin = atob(str);
    try {
      return decodeURIComponent(Array.from(bin).map(c => '%' + c.charCodeAt(0).toString(16).padStart(2,'0')).join(''));
    } catch { return bin; }
  } catch { return ""; }
}
function parseJwt(token){
  if (!token) return null;
  const parts = token.split('.');
  if (parts.length < 2) return null;
  try { return JSON.parse(b64urlDecode(parts[1])); } catch { return null; }
}
async function fetchJson(url, opts = {}){
  const res = await fetch(url, opts);
  const text = await res.text();
  let data = null;
  try { data = JSON.parse(text); } catch {}
  if (!res.ok) {
    const msg = (data && (data.message || data.error)) || text || res.statusText;
    throw new Error(msg || ('HTTP ' + res.status));
  }
  return data ?? {};
}

function setLoginButton(){
  const btn = document.getElementById('btn-auth');
  btn.textContent = 'เข้าสู่ระบบ';
  btn.className   = 'btn btn-success btn-sm';
  btn.href        = 'https://sci-sskru.com/hrm/login';
  btn.onclick     = null;
}
function setLogoutButton(){
  const btn = document.getElementById('btn-auth');
  btn.textContent = 'ออกจากระบบ';
  btn.className   = 'btn btn-danger btn-sm';
  btn.href        = '#';
  btn.onclick     = () => {
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
      try { localStorage.removeItem('hrm-sci-token'); } catch(e){}
      fetch(LOGOUT_URL, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } })
        .finally(()=> location.reload());
    }
    return false;
  };
}
function setSkeleton(on){
  const avatar = document.getElementById('avatar');
  const fields = ['fullName','email','dept_name','category_type_name','employee_type_name','academic_type_name'];
  avatar.classList.toggle('skeleton', on);
  fields.forEach(id=>{
    const el = document.getElementById(id);
    if (on) { el.classList.add('skeleton'); el.textContent = ' '; }
    else    { el.classList.remove('skeleton'); }
  });
}

async function render(){
  const statusEl = document.getElementById('status');
  const pidEl    = document.getElementById('pid');
  const card     = document.getElementById('profile-card');
  const avatar   = document.getElementById('avatar');

  const token = localStorage.getItem('hrm-sci-token');

  if (!token) {
    statusEl.className = 'alert alert-warning';
    statusEl.textContent = 'ยังไม่มีข้อมูล (ไม่พบ hrm-sci-token)';
    pidEl.textContent = 'ยังไม่มีข้อมูล';
    card.classList.add('d-none');
    setLoginButton();
    return;
  }

  statusEl.className = 'alert alert-success';
  statusEl.textContent = 'พบ hrm-sci-token';
  setLogoutButton();

  const payload = parseJwt(token);
  const personalId = payload?.personal_id || null;

  // ✅ เช็กวันหมดอายุของโทเคน
  const now = Math.floor(Date.now()/1000);
  if (payload?.exp && payload.exp < now) {
    try { localStorage.removeItem('hrm-sci-token'); } catch(e){}
    statusEl.className = 'alert alert-warning';
    statusEl.textContent = 'โทเคนหมดอายุแล้ว กรุณาเข้าสู่ระบบอีกครั้ง';
    pidEl.textContent = 'ยังไม่มีข้อมูล';
    card.classList.add('d-none');
    setLoginButton();
    return;
  }

  if (!personalId) {
    pidEl.textContent = 'ยังไม่มีข้อมูล';
    card.classList.add('d-none');
    statusEl.className = 'alert alert-danger';
    statusEl.textContent = 'พบโทเคน แต่ไม่พบ personal_id ใน payload';
    return;
  }

  pidEl.textContent = personalId;
  pidEl.parentElement.classList.add('badge-green');
  card.classList.remove('d-none');
  setSkeleton(true);

  try {
    // 1) ดึงโปรไฟล์จาก HRM
    const prof = await fetchJson('https://sci-sskru.com/authen/profile', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({ personal_id: personalId })
    });

    const titleName = prof.title_name ?? '';
    const firstName = prof.first_name ?? '';
    const lastName  = prof.last_name ?? '';
    const email     = prof.email ?? '-';
    const dept      = prof.dept_name ?? '-';
    const category  = prof.category_type_name ?? '-';
    const employee  = prof.employee_type_name ?? '-';
    const academic  = prof.academic_type_name ?? '-';
    const imgPath   = prof.img ?? '';
    const imgUrl    = imgPath ? ('https://sci-sskru.com/authen' + (imgPath.startsWith('/') ? '' : '/') + imgPath) : '';

    document.getElementById('fullName').textContent = `${titleName}${firstName} ${lastName}`.trim() || '-';
    document.getElementById('email').textContent    = email;
    document.getElementById('dept_name').textContent = dept;
    document.getElementById('category_type_name').textContent = category;
    document.getElementById('employee_type_name').textContent = employee;
    document.getElementById('academic_type_name').textContent = academic;

    if (imgUrl) {
      avatar.src = imgUrl;
      avatar.alt = `${firstName} ${lastName}`.trim() || 'avatar';
    } else {
      avatar.removeAttribute('src');
      avatar.alt = 'U';
      avatar.style.background = '#f3f4f6';
    }
    setSkeleton(false);

    // 2) ซิงก์เข้า PHP session ให้ทั้งแอปมองว่า "ล็อกอินแล้ว"
    await fetch(MY_PROFILE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
      body: JSON.stringify({ token, profile: prof })
    }).catch(()=>{});

    // 3) แจ้ง Navbar ให้รีเฟรชชื่อ/รูป (ถ้ามี listener)
    window.postMessage({ type: 'sso:logged-in', profile: prof }, window.origin);

  } catch (err) {
    setSkeleton(false);
    card.classList.add('d-none');
    statusEl.className = 'alert alert-danger';
    statusEl.textContent = 'ไม่สามารถดึงข้อมูลโปรไฟล์ได้: ' + (err?.message || 'Unknown error');
  }
}

document.addEventListener('DOMContentLoaded', render);
</script>
