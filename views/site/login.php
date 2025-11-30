<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;

/* บอก layout ว่านี่คือหน้า login → ไม่ต้องตรวจ token/redirect ซ้ำ */
$this->params['isLoginPage'] = true;

$csrf   = Yii::$app->request->getCsrfToken();
$sync   = Url::to(['/site/my-profile']); 
$logout = Url::to(['/site/logout']);
$index  = Url::to(['/site/index']);
?>
<div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
  <div class="container text-center" style="max-width:720px;">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div id="status" class="alert alert-info mb-4">กำลังตรวจสอบ...</div>

    <!-- เมื่อยังไม่มี token ให้กดไป login HRM -->
    <div id="login-cta" class="d-none">
      <a id="btn-login" href="https://sci-sskru.com/hrm/login" class="btn btn-success">คลิ๊กเข้าสู่ระบบ</a>
      <a href="<?= $index ?>" class="btn btn-outline-secondary ms-2" data-pjax="0">กลับหน้าแรก</a>
    </div>

    <!-- การ์ดโปรไฟล์ -->
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
            <div id="pid" class="text-muted small"></div>
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

    <!-- ปุ่มออกจากระบบ -->
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
const SYNC_URL   = <?= json_encode($sync) ?>;   // ← controller ที่จะสร้าง/อัปเดต tb_user และตั้ง position=1 ถ้าเป็น user ใหม่
const INDEX_URL  = <?= json_encode($index) ?>;
const API_PROFILE_URL = 'https://sci-sskru.com/authen/profile';

const $ = (id)=>document.getElementById(id);

/* --------- JWT utils --------- */
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

/* --------- fetch JSON helper --------- */
async function fetchJson(url, opts = {}){
  const res = await fetch(url, opts);
  const txt = await res.text();
  try { return JSON.parse(txt); } catch { return {}; }
}

/* --------- skeleton UI --------- */
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

/* --------- แสดงปุ่ม login แต่ "ไม่ลบ" token --------- */
function showCta(msg, type='warning'){
  const statusEl = $('status');
  const loginCta = $('login-cta');
  const card     = $('profile-card');
  const actions  = $('actions-logout');

  statusEl.className = 'alert alert-' + type + ' mb-4';
  statusEl.textContent = msg;
  loginCta.classList.remove('d-none');
  card.classList.add('d-none');
  actions.classList.add('d-none');
}

/* --------- main flow --------- */
(async function render(){
  const statusEl  = $('status');
  const loginCta  = $('login-cta');
  const card      = $('profile-card');
  const actions   = $('actions-logout');

  const token = localStorage.getItem('hrm-sci-token');
  const urlParams = new URLSearchParams(location.search);
  let redirectTo = urlParams.get('redirect') || INDEX_URL;

  // ✅ ป้องกัน open redirect: ให้ใช้ origin เดียวกันเท่านั้น
  try {
    const tmpUrl = new URL(redirectTo, location.origin);
    if (tmpUrl.origin !== location.origin) {
      redirectTo = INDEX_URL;
    } else {
      redirectTo = tmpUrl.href;
    }
  } catch (e) {
    redirectTo = INDEX_URL;
  }


  // 1) ไม่มีโทเคนเลย → ให้ไป login ที่ HRM
  if (!token) {
    showCta('ยังไม่มีข้อมูลโทเคน (ไม่พบ hrm-sci-token)');
    return;
  }

  // 2) เช็ก payload / exp
  const payload = parseJwt(token) || {};
  const personalId = payload.personal_id || payload.uname || null;
  const leeway = 120; // เผื่อเวลาเบี้ยว
  const now = Math.floor(Date.now()/1000);

  if (Number.isFinite(payload.exp) && (payload.exp + leeway) < now) {
    showCta('โทเคนหมดอายุแล้ว กรุณาเข้าสู่ระบบอีกครั้ง');
    return;
  }
  if (!personalId){
    showCta('พบโทเคน แต่ไม่มี personal_id/uname ใน payload', 'danger');
    return;
  }

  // 3) แสดง UI ว่า token ใช้ได้
  statusEl.className = 'alert alert-success mb-4';
  statusEl.textContent = 'ยืนยันโทเคนแล้ว (ID: ' + personalId + ')';
  loginCta.classList.add('d-none');
  card.classList.remove('d-none');
  actions.classList.remove('d-none');
  startPlaceholders();

  // 4) ดึงโปรไฟล์จาก HRM
  let profile = {};
  try {
    const raw = await fetchJson(API_PROFILE_URL, {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'Authorization':'Bearer '+token },
      body: JSON.stringify({ personal_id: personalId })
    });
    // บางที HRM ตอบ {profile:{...}} บางที {...}
    profile = (raw && typeof raw === 'object') ? (raw.profile || raw || {}) : {};
  } catch(e) {
    profile = {};
  }

  // 5) อัปเดต DOM ให้ดูสวย
  try {
    // fallback จาก payload ถ้า HRM ไม่ให้มา
    const titleName = profile.title_name ?? '';
    const firstName = profile.first_name ?? payload.first_name ?? '';
    const lastName  = profile.last_name  ?? payload.last_name  ?? '';
    const email     = profile.email
                      ?? profile.email_uni_google
                      ?? profile.email_uni_microsoft
                      ?? payload.email
                      ?? '-';
    const dept      = profile.dept_name ?? '-';
    const category  = profile.category_type_name ?? '-';
    const employee  = profile.employee_type_name ?? '-';
    const academic  = profile.academic_type_name ?? '-';
    const HRM_BASE = 'https://sci-sskru.com/authen';
      function buildImgUrl(path) {
        if (!path) return '';
        // ถ้าเป็น URL เต็มอยู่แล้ว ไม่ต้อง prefix
        if (/^https?:\/\//i.test(path)) {
          return path;
        }
        return HRM_BASE + (path.startsWith('/') ? '' : '/') + path;
      }

      // ...

      const imgUrl = buildImgUrl(profile.img);


    $('fullName').textContent = (`${titleName}${firstName} ${lastName}`).trim() || '-';
    $('email').textContent    = email;
    $('pid').textContent      = personalId ? ('รหัสบุคลากร: ' + personalId) : '';
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

 
  try {
    const res = await fetch(SYNC_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF_TOKEN
      },
      body: JSON.stringify({ token, profile })
    });

    const text = await res.text();   // อ่าน raw text มาก่อน
    let data = {};

    try {
      data = JSON.parse(text);       // พยายาม parse JSON
    } catch (e) {
      console.error('❌ SYNC: JSON parse error. Raw response:', text);

      statusEl.className = 'alert alert-danger mb-4';
      statusEl.textContent = 'เซิร์ฟเวอร์ตอบกลับไม่ใช่ JSON (อาจเป็นหน้า error / CSRF / 500)';

      loginCta.classList.remove('d-none');
      return;
    }

    console.log('🔍 SYNC /site/my-profile → status:', res.status, 'data:', data);

    // ✅ กรณีสำเร็จ
    if (res.ok && data && data.ok) {
      statusEl.className = 'alert alert-success mb-4';
      statusEl.textContent = 'เข้าสู่ระบบสำเร็จ กำลังเปลี่ยนหน้า...';
      window.location.href = redirectTo;
      return;
    }

    // ❌ กรณี backend ตอบ ok=false หรือ res.ok=false
    let msg = 'ไม่สามารถ sync ข้อมูลเข้าสู่ระบบได้ (token ยังอยู่ใน browser)';

    if (data && typeof data === 'object') {

      switch (data.error) {
        case 'no token':
          msg = 'ระบบไม่ได้รับ token จาก browser (no token) กรุณาลองเข้าสู่ระบบใหม่อีกครั้ง';
          break;

        case 'payload too large':
          msg = 'ข้อมูลที่ส่งไปยังเซิร์ฟเวอร์มีขนาดใหญ่เกินกำหนด (payload too large)';
          break;

        case 'profile has no username/personal_id':
          msg = 'ข้อมูลโปรไฟล์จาก SSO ไม่มี username หรือ personal_id ไม่สามารถสร้างบัญชีได้';
          break;

        case 'fromToken error':
          msg = 'ไม่สามารถแปลง token เป็นผู้ใช้ได้ (fromToken error)'
                + (data.message ? '\n' + data.message : '');
          break;

        case 'validate fail':
          msg = 'ข้อมูลผู้ใช้จาก SSO ไม่ผ่านการตรวจสอบ (validate fail)';
          if (data.detail) {
            try {
              msg += '\nรายละเอียด: ' + JSON.stringify(data.detail);
            } catch (e) {}
          }
          break;

        case 'db error':
          msg = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลผู้ใช้ลงฐานข้อมูล (db error)';
          if (data.message) {
            msg += '\n' + data.message;
          }
          break;

        case 'login error':
          msg = 'สร้าง/อัปเดตข้อมูลผู้ใช้ได้แล้ว แต่เข้าสู่ระบบไม่สำเร็จ (login error)';
          if (data.message) {
            msg += '\n' + data.message;
          }
          break;

        default:
          if (data.error) {
            msg = data.error;
          }
          break;
      }
    }

    statusEl.className = 'alert alert-warning mb-4';
    statusEl.textContent = msg;
    loginCta.classList.remove('d-none');

  } catch (e) {
    console.error('❌ SYNC /site/my-profile network/JS error:', e);

    statusEl.className = 'alert alert-danger mb-4';
    statusEl.textContent = 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ (network หรือ JavaScript error) กรุณาลองใหม่หรือติดต่อผู้ดูแล';

    loginCta.classList.remove('d-none');
  }
})();
/* --------- เคลียร์ storage ตอน "ผู้ใช้" กดออกเองเท่านั้น --------- */
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
