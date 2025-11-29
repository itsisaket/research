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

    // ✅ จัดการรูป: รองรับทั้ง path และ URL เต็ม
    const HRM_BASE = 'https://sci-sskru.com/authen';
    function buildImgUrl(path) {
      if (!path) return '';
      // ถ้าเป็น URL เต็มอยู่แล้ว ไม่ต้อง prefix
      if (/^https?:\/\//i.test(path)) {
        return path;
      }
      return HRM_BASE + (path.startsWith('/') ? '' : '/') + path;
    }
    const imgUrl = buildImgUrl(profile.img);

    $('fullName').textContent = (`${titleName}${firstName} ${lastName}`).trim() || '-';
    $('email').textContent    = email;
    $('pid').textContent      = personalId ? ('รหัสบุคลากร: ' + personalId) : '';
    $('dept_name').textContent = dept;
    $('category_type_name').textContent = category;
    $('employee_type_name').textContent = employee;
    $('academic_type_name').textContent = academic;

    // ✅ อัปเดตรูป avatar ทั้งการ์ดและ navbar
    if (imgUrl) {
      // รูปใน card หน้า login
      $('avatar').src = imgUrl;
      $('avatar').alt = (`${firstName} ${lastName}`).trim() || 'avatar';
      $('avatar').onerror = () => { $('avatar').removeAttribute('src'); };

      // รูปใน navbar (layout) ถ้ามี
      const navAvatar = document.getElementById('nav-avatar');
      if (navAvatar) {
        navAvatar.src = imgUrl;
      }
    } else {
      $('avatar').removeAttribute('src');
      $('avatar').alt = 'avatar';
    }

    stopPlaceholders();
  } catch {
    stopPlaceholders();
  }

  // 6) SYNC เข้าระบบ SES
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

    statusEl.className = 'alert alert-warning mb-4';
    statusEl.textContent = data?.error || 'ไม่สามารถ sync ข้อมูลเข้าสู่ระบบได้ (token ยังอยู่ใน browser)';
    loginCta.classList.remove('d-none');

  } catch(e){
    statusEl.className = 'alert alert-warning mb-4';
    statusEl.textContent = 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ ขอลองใหม่หรือติดต่อผู้ดูแลระบบ';
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
