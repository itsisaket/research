<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;

$csrf   = Yii::$app->request->getCsrfToken();
$sync   = Url::to(['/site/my-profile']); // POST: sync identity into PHP session
$logout = Url::to(['/site/logout']);     // POST: Yii logout
?>
<div class="container py-4">
  <h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

  <!-- กล่องสถานะ -->
  <div id="box-status" class="card border-0 bg-light mb-3">
    <div class="card-body">
      <div id="status" class="alert alert-info mb-3">กำลังตรวจสอบ...</div>

      <!-- แถบสรุป (not logged in) -->
      <div id="login-cta" class="d-none">
        <p class="mb-3 text-muted">
          <span class="fw-semibold">Login:</span>
          <span class="ms-1">ยังไม่มีข้อมูล (ไม่พบ <code>hrm-sci-token</code>)</span>
        </p>
        <a id="btn-login" href="https://sci-sskru.com/hrm/login" class="btn btn-success">
          คลิ๊กเข้าสู่ระบบ
        </a>
      </div>

      <!-- แถบสรุป (logged in) -->
      <div id="logout-cta" class="d-none">
        <div class="mb-2">
          <span class="badge rounded-pill text-bg-success">personal_id:
            <span id="pid">-</span>
          </span>
        </div>

        <!-- โปรไฟล์ย่อ -->
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-3">
              <img id="avatar" alt="avatar" class="rounded-circle border bg-light"
                   style="width:96px;height:96px;object-fit:cover;">
              <div class="flex-grow-1">
                <div id="fullName" class="fw-semibold placeholder-glow">
                  <span class="placeholder col-6"></span>
                </div>
                <div id="email" class="text-muted small placeholder-glow">
                  <span class="placeholder col-4"></span>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <div class="border rounded p-2">
                  <div class="text-muted small">หน่วยงาน</div>
                  <div id="dept_name" class="fw-semibold placeholder-glow">
                    <span class="placeholder col-8"></span>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-2">
                  <div class="text-muted small">สายงาน</div>
                  <div id="category_type_name" class="fw-semibold placeholder-glow">
                    <span class="placeholder col-6"></span>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-2">
                  <div class="text-muted small">ประเภทพนักงาน</div>
                  <div id="employee_type_name" class="fw-semibold placeholder-glow">
                    <span class="placeholder col-6"></span>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-2">
                  <div class="text-muted small">ตำแหน่งวิชาการ</div>
                  <div id="academic_type_name" class="fw-semibold placeholder-glow">
                    <span class="placeholder col-6"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <a id="btn-logout" href="#" class="btn btn-danger">คลิ๊กออกจากระบบ</a>
      </div>

      <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-outline-secondary ms-2">กลับหน้าแรก</a>
    </div>
  </div>
</div>

<script>
/* ===== Config ===== */
const CSRF_TOKEN = '<?= $csrf ?>';
const SYNC_URL   = '<?= $sync ?>';
const LOGOUT_URL = '<?= $logout ?>';
const API_PROFILE_URL = 'https://sci-sskru.com/authen/profile';
const TIMEOUT_MS = 8000;

/* ===== Helpers ===== */
const $ = (id) => document.getElementById(id);

function b64urlDecode(str){
  try {
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    const pad = str.length % 4; if (pad) str += '='.repeat(4 - pad);
    const bin = atob(str);
    try { return decodeURIComponent(Array.from(bin).map(c => '%' + c.charCodeAt(0).toString(16).padStart(2,'0')).join('')); }
    catch { return bin; }
  } catch { return ""; }
}
function parseJwt(token){
  if (!token) return null;
  const parts = token.split('.'); if (parts.length < 2) return null;
  try { return JSON.parse(b64urlDecode(parts[1])); } catch { return null; }
}
async function fetchJson(url, opts = {}, timeoutMs = TIMEOUT_MS){
  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), timeoutMs);
  try {
    const res = await fetch(url, { ...opts, signal: ctrl.signal });
    const txt = await res.text();
    let data = null; try { data = JSON.parse(txt); } catch {}
    if (!res.ok) throw new Error((data && (data.message || data.error)) || txt || res.statusText);
    return data ?? {};
  } finally { clearTimeout(t); }
}
function startPlaceholders(){
  ['fullName','email','dept_name','category_type_name','employee_type_name','academic_type_name']
    .forEach(id => $(id).classList.add('placeholder-glow'));
  $('avatar').removeAttribute('src');
  $('avatar').classList.add('bg-light');
}
function stopPlaceholders(){
  ['fullName','email','dept_name','category_type_name','employee_type_name','academic_type_name']
    .forEach(id => $(id).classList.remove('placeholder-glow'));
  $('avatar').classList.remove('bg-light');
}
function buildImageUrl(imgPath){
  if (!imgPath) return '';
  return 'https://sci-sskru.com/authen' + (imgPath.startsWith('/') ? '' : '/') + imgPath;
}

/* ===== UI wiring ===== */
$('btn-logout').onclick = () => {
  if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
    try { localStorage.removeItem('hrm-sci-token'); } catch(e){}
    fetch(LOGOUT_URL, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } })
      .finally(()=> location.reload());
  }
  return false;
};

/* ===== Main ===== */
async function render(){
  const statusEl  = $('status');
  const loginCta  = $('login-cta');
  const logoutCta = $('logout-cta');

  const token = localStorage.getItem('hrm-sci-token');

  if (!token) {
    statusEl.className = 'alert alert-warning mb-3';
    statusEl.textContent = 'ยังไม่มีข้อมูล (ไม่พบ hrm-sci-token)';
    loginCta.classList.remove('d-none');
    logoutCta.classList.add('d-none');
    return;
  }

  // ตรวจ payload
  const payload = parseJwt(token);
  const personalId = payload?.personal_id || null;
  const now = Math.floor(Date.now()/1000);
  if (payload?.exp && payload.exp < now) {
    try { localStorage.removeItem('hrm-sci-token'); } catch(e){}
    statusEl.className = 'alert alert-warning mb-3';
    statusEl.textContent = 'โทเคนหมดอายุแล้ว กรุณาเข้าสู่ระบบอีกครั้ง';
    loginCta.classList.remove('d-none');
    logoutCta.classList.add('d-none');
    return;
  }
  if (!personalId) {
    statusEl.className = 'alert alert-danger mb-3';
    statusEl.textContent = 'พบโทเคน แต่ไม่พบ personal_id ใน payload';
    loginCta.classList.remove('d-none');
    logoutCta.classList.add('d-none');
    return;
  }

  // โชว์ UI logged-in
  statusEl.className = 'alert alert-success mb-3';
  statusEl.textContent = 'เข้าสู่ระบบแล้ว';
  loginCta.classList.add('d-none');
  logoutCta.classList.remove('d-none');

  $('pid').textContent = personalId;
  startPlaceholders();

  try {
    // ดึงโปรไฟล์
    const raw = await fetchJson(API_PROFILE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({ personal_id: personalId })
    });
    const p = raw?.profile ?? raw ?? {};

    const titleName = p.title_name ?? '';
    const firstName = p.first_name ?? '';
    const lastName  = p.last_name ?? '';
    const email     = p.email ?? p.email_uni_google ?? p.email_uni_microsoft ?? '-';
    const dept      = p.dept_name ?? '-';
    const category  = p.category_type_name ?? '-';
    const employee  = p.employee_type_name ?? '-';
    const academic  = p.academic_type_name ?? '-';
    const imgUrl    = buildImageUrl(p.img ?? '');

    $('fullName').textContent = (`${titleName}${firstName} ${lastName}`).trim() || '-';
    $('email').textContent    = email;
    $('dept_name').textContent = dept;
    $('category_type_name').textContent = category;
    $('employee_type_name').textContent = employee;
    $('academic_type_name').textContent = academic;

    if (imgUrl) {
      $('avatar').src = imgUrl;
      $('avatar').alt = (`${firstName} ${lastName}`).trim() || 'avatar';
      $('avatar').onerror = () => { $('avatar').removeAttribute('src'); };
    } else {
      $('avatar').removeAttribute('src');
      $('avatar').alt = 'avatar';
    }
    stopPlaceholders();

    // sync session (fire-and-forget)
    fetch(SYNC_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
      body: JSON.stringify({ token, profile: p })
    }).catch(()=>{});

    // แจ้ง navbar (ถ้ามี listener)
    window.postMessage({ type: 'sso:logged-in', profile: p }, window.origin);

  } catch (e) {
    stopPlaceholders();
    statusEl.className = 'alert alert-danger mb-3';
    statusEl.textContent = 'ไม่สามารถดึงข้อมูลโปรไฟล์ได้: ' + (e?.message || 'Unknown error');
    // ย้อนกลับไปสถานะ login
    loginCta.classList.remove('d-none');
    logoutCta.classList.add('d-none');
  }
}

document.addEventListener('DOMContentLoaded', render);
</script>
