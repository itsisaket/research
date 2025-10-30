<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;

/* บอก layout ว่านี่คือหน้า login → ไม่ต้องตรวจ token/redirect ซ้ำ */
$this->params['isLoginPage'] = true;

$csrf   = Yii::$app->request->getCsrfToken();
$sync   = Url::to(['/site/my-profile']); // ✅ ตัวรับ sync
$logout = Url::to(['/site/logout']);
$index  = Url::to(['/site/index']);
?>
<div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
  <div class="container text-center" style="max-width:720px;">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div id="status" class="alert alert-info mb-4">กำลังตรวจสอบ...</div>

    <div id="login-cta" class="d-none">
      <a id="btn-login" href="https://sci-sskru.com/hrm/login" class="btn btn-success">คลิ๊กเข้าสู่ระบบ</a>
      <a href="<?= $index ?>" class="btn btn-outline-secondary ms-2" data-pjax="0">กลับหน้าแรก</a>
    </div>

    <div id="profile-card" class="card shadow-sm mx-auto d-none">
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

    <div id="actions-logout" class="d-none mt-4">
      <?php
        echo Html::beginForm(['site/logout'], 'post', [
          'id' => 'page-logout-form',
          'class' => 'd-inline',
          'data-pjax' => '0',
        ]);
      ?>
        <button type="submit" class="btn btn-danger" id="page-logout-btn">
          คลิ๊กออกจากระบบ
        </button>
      <?php echo Html::endForm(); ?>

      <a href="<?= $index ?>" class="btn btn-outline-secondary ms-2" data-pjax="0">
        กลับหน้าแรก
      </a>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = <?= json_encode($csrf) ?>;
const SYNC_URL   = <?= json_encode($sync) ?>;
const LOGOUT_URL = <?= json_encode($logout) ?>;
const INDEX_URL  = <?= json_encode($index) ?>;
const API_PROFILE_URL = 'https://sci-sskru.com/authen/profile';

const $ = (id)=>document.getElementById(id);

function parseJwt(token){
  if (!token) return null;
  const p = token.split('.');
  if (p.length < 2) return null;
  try {
    let payload = p[1].replace(/-/g,'+').replace(/_/g,'/');
    const pad = payload.length % 4; if (pad) payload += '='.repeat(4 - pad);
    return JSON.parse(atob(payload));
  } catch { return null; }
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

// ✅ เวอร์ชันเบา: แค่โชว์ปุ่ม ไม่ต้องบังคับ logout เซิร์ฟเวอร์
function showCta(msg, type='warning'){
  const statusEl = $('status');
  const loginCta = $('login-cta');
  const card     = $('profile-card');
  const actions  = $('actions-logout');

  try { localStorage.removeItem('hrm-sci-token'); } catch(e){}
  statusEl.className = 'alert alert-' + type + ' mb-4';
  statusEl.textContent = msg;
  loginCta.classList.remove('d-none');
  card.classList.add('d-none');
  actions.classList.add('d-none');
}

(async function render(){
  const statusEl  = $('status');
  const loginCta  = $('login-cta');
  const card      = $('profile-card');
  const actions   = $('actions-logout');

  const token = localStorage.getItem('hrm-sci-token');

  const urlParams = new URLSearchParams(location.search);
  const redirectTo = urlParams.get('redirect') || INDEX_URL;

  if (!token) {
    showCta('ยังไม่มีข้อมูลโทเคน (ไม่พบ hrm-sci-token)');
    return;
  }

  const payload = parseJwt(token) || {};
  const personalId = payload.personal_id || payload.uname || null;
  const leeway = 120;
  const now = Math.floor(Date.now()/1000);

  if (Number.isFinite(payload.exp) && (payload.exp + leeway) < now) {
    showCta('โทเคนหมดอายุแล้ว กรุณาเข้าสู่ระบบอีกครั้ง');
    return;
  }
  if (!personalId){
    showCta('พบโทเคน แต่ไม่มี personal_id/uname ใน payload', 'danger');
    return;
  }

  statusEl.className = 'alert alert-success mb-4';
  statusEl.textContent = 'ยืนยันโทเคนแล้ว (ID: ' + personalId + ')';
  loginCta.classList.add('d-none');
  card.classList.remove('d-none');
  actions.classList.remove('d-none');
  startPlaceholders();

  // ดึงโปรไฟล์จาก HRM
  let profile = {};
  try {
    const raw = await fetchJson(API_PROFILE_URL, {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'Authorization':'Bearer '+token },
      body: JSON.stringify({ personal_id: personalId })
    });
    profile = (raw && typeof raw === 'object') ? (raw.profile || raw || {}) : {};
  } catch(e) {
    profile = {};
  }

  // อัปเดต DOM
  try {
    const p = profile || {};
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
  } catch {
    stopPlaceholders();
  }

  // ✅ sync เข้า Yii
  try {
    const res = await fetch(SYNC_URL, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-Token': CSRF_TOKEN
      },
      body: JSON.stringify({ token, profile })
    });
    const data = await res.json().catch(()=>({}));

    if (res.ok && data && data.ok) {
      statusEl.className = 'alert alert-success mb-4';
      statusEl.textContent = 'เข้าสู่ระบบสำเร็จ กำลังเปลี่ยนหน้า...';
      window.location.href = redirectTo;
      return;
    }

    // ถ้า backend ตอบไม่ใช่ ok → แค่แสดงปุ่ม login
    showCta(data?.error || 'ไม่สามารถ sync session ได้');
  } catch(e){
    showCta('เกิดข้อผิดพลาดระหว่างเชื่อมต่อเซิร์ฟเวอร์');
  }
})();

// เคลียร์ storage ตอนกด logout
(function(){
  var form = document.getElementById('page-logout-form');
  if (!form) return;
  form.addEventListener('submit', function(){
    try {
      localStorage.removeItem('hrm-sci-token');
      localStorage.removeItem('userInfo');
      localStorage.removeItem('accessToken');
      sessionStorage.clear();
    } catch(e) {}
  });
})();
</script>
