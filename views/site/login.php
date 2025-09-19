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
<div class="container py-5 text-center">
  <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

  <!-- แถบสถานะ -->
  <div class="mx-auto" style="max-width:720px;">
    <div id="status" class="alert alert-info mb-4">กำลังตรวจสอบ...</div>
  </div>

  <!-- เมื่อ "ยังไม่ login" -->
  <div id="login-cta" class="d-none">
    <a id="btn-login" href="https://sci-sskru.com/hrm/login" class="btn btn-success">คลิ๊กเข้าสู่ระบบ</a>
    <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-outline-secondary ms-2">กลับหน้าแรก</a>
  </div>

  <!-- การ์ดโปรไฟล์ (แสดงเมื่อ login สำเร็จ) -->
  <div id="profile-card" class="card shadow-sm mx-auto d-none" style="max-width:720px;">
    <div class="card-body">
      <div class="d-flex align-items-start gap-3 mb-3 justify-content-center">
        <img id="avatar" alt="avatar" class="rounded-circle border bg-light"
             style="width:96px;height:96px;object-fit:cover;">
        <div class="text-start">
          <div id="fullName" class="fw-semibold placeholder-glow">
            <span class="placeholder col-6"></span>
          </div>
          <div id="email" class="text-muted small placeholder-glow">
            <span class="placeholder col-4"></span>
          </div>
        </div>
      </div>

      <div class="row g-3 text-start">
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

  <!-- แถวปุ่ม (อยู่ "ด้านล่าง" เสมอ) -->
  <div id="actions-logout" class="d-none mt-4">
    <a id="btn-logout" href="#" class="btn btn-danger">คลิ๊กออกจากระบบ</a>
    <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-outline-secondary ms-2">กลับหน้าแรก</a>
  </div>
</div>

<script>
const CSRF_TOKEN = '<?= $csrf ?>';
const SYNC_URL   = '<?= $sync ?>';
const LOGOUT_URL = '<?= $logout ?>';
const API_PROFILE_URL = 'https://sci-sskru.com/authen/profile';

const $ = (id)=>document.getElementById(id);

function parseJwt(token){
  if (!token) return null;
  const p = token.split('.');
  if (p.length < 2) return null;
  try { return JSON.parse(atob(p[1].replace(/-/g,'+').replace(/_/g,'/'))); }
  catch { return null; }
}
async function fetchJson(url, opts = {}){
  const res = await fetch(url, opts);
  const txt = await res.text();
  try { return JSON.parse(txt); } catch { return {}; }
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

async function render(){
  const statusEl  = $('status');
  const loginCta  = $('login-cta');
  const card      = $('profile-card');
  const actions   = $('actions-logout');

  const token = localStorage.getItem('hrm-sci-token');

  // ไม่พบโทเคน → โชว์ปุ่มเข้าสู่ระบบ
  if (!token) {
    statusEl.className = 'alert alert-warning mb-4';
    statusEl.textContent = 'ยังไม่มีข้อมูล (ไม่พบ hrm-sci-token)';
    loginCta.classList.remove('d-none');
    card.classList.add('d-none');
    actions.classList.add('d-none');
    return;
  }

  // ตรวจ payload
  const payload = parseJwt(token);
  const personalId = payload?.personal_id || null;
  const now = Math.floor(Date.now()/1000);
  if (payload?.exp && payload.exp < now) {
    localStorage.removeItem('hrm-sci-token');
    statusEl.className = 'alert alert-warning mb-4';
    statusEl.textContent = 'โทเคนหมดอายุแล้ว กรุณาเข้าสู่ระบบอีกครั้ง';
    loginCta.classList.remove('d-none');
    card.classList.add('d-none');
    actions.classList.add('d-none');
    return;
  }
  if (!personalId){
    statusEl.className = 'alert alert-danger mb-4';
    statusEl.textContent = 'พบโทเคน แต่ไม่พบ personal_id';
    loginCta.classList.remove('d-none');
    card.classList.add('d-none');
    actions.classList.add('d-none');
    return;
  }

  // --- show logged-in ---
  // แถบด้านบนต้องแสดง personal_id
  statusEl.className = 'alert alert-success mb-4';
  statusEl.textContent = 'personal_id: ' + personalId;

  loginCta.classList.add('d-none');
  card.classList.remove('d-none');
  actions.classList.remove('d-none');
  startPlaceholders();

  try {
    const raw = await fetchJson(API_PROFILE_URL, {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'Authorization':'Bearer '+token },
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
    const imgUrl    = p.img ? ('https://sci-sskru.com/authen' + (p.img.startsWith('/')?'':'/') + p.img) : '';

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

    // sync session (ไม่บล็อค UI)
    fetch(SYNC_URL, {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN },
      body: JSON.stringify({ token, profile: p })
    }).catch(()=>{});

  } catch(e){
    stopPlaceholders();
    statusEl.className = 'alert alert-danger mb-4';
    statusEl.textContent = 'โหลดโปรไฟล์ไม่สำเร็จ';
  }

  // ปุ่มออกจากระบบ
  $('btn-logout').onclick = ()=>{
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')){
      localStorage.removeItem('hrm-sci-token');
      fetch(LOGOUT_URL,{ method:'POST', headers:{ 'X-CSRF-Token': CSRF_TOKEN } })
        .finally(()=>location.reload());
    }
    return false;
  };
}

document.addEventListener('DOMContentLoaded', render);
</script>
