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

  <!-- กล่องสถานะ -->
  <div id="box-status" class="card border-0 bg-light shadow-sm mx-auto" style="max-width:600px;">
    <div class="card-body">

      <!-- แสดงสถานะ -->
      <div id="status" class="alert alert-info mb-4">กำลังตรวจสอบ...</div>

      <!-- login ยังไม่พบโทเคน -->
      <div id="login-cta" class="d-none">
        <a id="btn-login" href="https://sci-sskru.com/hrm/login" class="btn btn-success">
          คลิ๊กเข้าสู่ระบบ
        </a>
        <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-outline-secondary ms-2">
          กลับหน้าแรก
        </a>
      </div>

      <!-- login สำเร็จ -->
      <div id="logout-cta" class="d-none">
        <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mb-4">
          <span id="pid" class="badge rounded-pill text-bg-success fs-6 px-3 py-2">
            personal_id: -
          </span>
          <a id="btn-logout" href="#" class="btn btn-danger">คลิ๊กออกจากระบบ</a>
          <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-outline-secondary">กลับหน้าแรก</a>
        </div>

        <!-- โปรไฟล์ย่อ -->
        <div class="card shadow-sm">
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
      </div>

    </div>
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
  try {
    const dec = atob(p[1].replace(/-/g,'+').replace(/_/g,'/'));
    return JSON.parse(dec);
  } catch { return null; }
}

async function fetchJson(url, opts = {}){
  const res = await fetch(url, opts);
  const txt = await res.text();
  try { return JSON.parse(txt); } catch { return {}; }
}

/* ===== Main ===== */
async function render(){
  const statusEl  = $('status');
  const loginCta  = $('login-cta');
  const logoutCta = $('logout-cta');
  const pidEl     = $('pid');
  const avatar    = $('avatar');

  const token = localStorage.getItem('hrm-sci-token');

  if (!token){
    statusEl.className = 'alert alert-warning mb-4';
    statusEl.textContent = 'ยังไม่มีข้อมูล (ไม่พบ hrm-sci-token)';
    loginCta.classList.remove('d-none');
    logoutCta.classList.add('d-none');
    return;
  }

  const payload = parseJwt(token);
  const personalId = payload?.personal_id || null;
  if (!personalId){
    statusEl.className = 'alert alert-danger mb-4';
    statusEl.textContent = 'พบโทเคน แต่ไม่พบ personal_id';
    loginCta.classList.remove('d-none');
    logoutCta.classList.add('d-none');
    return;
  }

  // show logged-in
  statusEl.className = 'alert alert-success mb-4';
  statusEl.textContent = '';
  loginCta.classList.add('d-none');
  logoutCta.classList.remove('d-none');
  pidEl.textContent = 'personal_id: ' + personalId;

  try {
    const raw = await fetchJson(API_PROFILE_URL, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Authorization':'Bearer '+token
      },
      body: JSON.stringify({ personal_id: personalId })
    });
    const p = raw?.profile ?? raw ?? {};
    $('fullName').textContent = (p.title_name ?? '')+(p.first_name ?? '')+' '+(p.last_name ?? '');
    $('email').textContent    = p.email ?? p.email_uni_google ?? p.email_uni_microsoft ?? '-';
    $('dept_name').textContent = p.dept_name ?? '-';
    $('category_type_name').textContent = p.category_type_name ?? '-';
    $('employee_type_name').textContent = p.employee_type_name ?? '-';
    $('academic_type_name').textContent = p.academic_type_name ?? '-';
    if (p.img){
      avatar.src = 'https://sci-sskru.com/authen' + (p.img.startsWith('/')?'':'/') + p.img;
    }
  } catch(e){
    statusEl.className = 'alert alert-danger mb-4';
    statusEl.textContent = 'โหลดโปรไฟล์ไม่สำเร็จ';
  }

  // logout button
  $('btn-logout').onclick = ()=>{
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')){
      localStorage.removeItem('hrm-sci-token');
      fetch(LOGOUT_URL,{method:'POST',headers:{'X-CSRF-Token':CSRF_TOKEN}})
        .finally(()=>location.reload());
    }
    return false;
  };
}
document.addEventListener('DOMContentLoaded', render);
</script>
