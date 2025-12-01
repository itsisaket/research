<?php
/** @var yii\web\View $this */
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'About';
$this->params['breadcrumbs'][] = $this->title;
$this->params['isLoginPage'] = true;

$csrf = Yii::$app->request->getCsrfToken();
$syncUrl = Url::to(['/site/up-user-json']);
?>
<div class="site-about">
  <h1><?= Html::encode($this->title) ?></h1>
  <p>This is the About page. You may modify the following file to customize its content:</p>
  <code><?= __FILE__ ?></code>
</div>

<!-- ปุ่ม Sync -->
<button type="button"
        id="btn-sync-hrm"
        class="btn btn-primary mb-3">
  🔄 Sync บุคลากรจาก HRM
</button>

<hr>

<!-- LocalStorage viewer -->
<div class="container py-4">
  <p class="text-muted">ข้อมูลที่บันทึกไว้ใน localStorage:</p>
  <table class="table table-bordered">
    <thead><tr><th>Key</th><th>Value</th></tr></thead>
    <tbody id="ls-table"><tr><td colspan="2" class="text-center">ไม่มีข้อมูลใน localStorage</td></tr></tbody>
  </table>
</div>

<!-- JWT payload -->
<div class="container py-4">
  <h5>JWT Payload (จาก <code>hrm-sci-token</code>)</h5>
  <pre id="jwt-json" style="background:#fff7e6; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
</div>

<!-- Profile result -->
<div class="container py-4">
  <h5>ข้อมูลผู้ใช้ (JSON จาก API <code>/authen/profile</code>)</h5>
  <div class="small text-muted mb-2" id="profile-meta"></div>
  <pre id="profile-json" style="background:#f8f9fa; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
</div>

<!-- List profiles result -->
<div class="container py-4">
  <h5>ข้อมูลรายชื่อ (JSON จาก API <code>/authen/list-profiles</code>)</h5>
  <div class="small text-muted mb-2" id="list-meta"></div>
  <pre id="list-json" style="background:#f1f8ff; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
</div>

<script>
document.addEventListener("DOMContentLoaded", async () => {
  const tbody       = document.getElementById("ls-table");
  const jwtPre      = document.getElementById("jwt-json");
  const profilePre  = document.getElementById("profile-json");
  const profileMeta = document.getElementById("profile-meta");
  const listPre     = document.getElementById("list-json");
  const listMeta    = document.getElementById("list-meta");
  const btnSync     = document.getElementById("btn-sync-hrm");

  const csrfToken   = <?= json_encode($csrf) ?>;
  const SYNC_URL   = <?= json_encode($sync) ?>;
  const syncUrl     = <?= json_encode($syncUrl) ?>;

  const API_PROFILE_URL     = 'https://sci-sskru.com/authen/profile';
  const API_FACULTIES_URL   = 'https://sci-sskru.com/authen/list-facultys';
  const API_DEPARTMENTS_URL = 'https://sci-sskru.com/authen/list-departments';

  // -------- 1) แสดง localStorage --------
  tbody.innerHTML = "";
  if (localStorage.length === 0) {
    tbody.innerHTML = "<tr><td colspan='2' class='text-center'>ไม่มีข้อมูล</td></tr>";
  } else {
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i);
      const v = localStorage.getItem(k);
      tbody.insertAdjacentHTML("beforeend", `<tr><td>${k}</td><td>${v}</td></tr>`);
    }
  }

  // -------- 2) helpers --------
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
    if (!token || token.split('.').length < 2) return null;
    try { return JSON.parse(b64urlDecode(token.split('.')[1])); } catch { return null; }
  }
  async function fetchJson(url, opts){
    const res  = await fetch(url, opts);
    const text = await res.text();
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}: ${text}`);
    try { return JSON.parse(text); } catch { return text; }
  }
  function show(preEl, data){
    preEl.textContent = (typeof data === "string") ? data : JSON.stringify(data, null, 2);
  }

  // -------- 3) ดึง token และ personal_id จาก JWT --------
  const token = localStorage.getItem("hrm-sci-token");
  if (!token){
    jwtPre.textContent     = "ไม่พบ hrm-sci-token ใน localStorage";
    profilePre.textContent = "ไม่พบ hrm-sci-token ใน localStorage";
    listPre.textContent    = "ไม่พบ hrm-sci-token ใน localStorage";
    // ปุ่ม Sync: disable ถ้าไม่มี token
    if (btnSync) {
      btnSync.disabled = true;
      btnSync.textContent = "ไม่มี token SSO (Sync ใช้งานไม่ได้)";
    }
    return;
  }

  const payload   = parseJwt(token) || {};
  show(jwtPre, payload);

  const personalId = payload.personal_id;
  if (!personalId){
    profilePre.textContent = "ไม่พบ personal_id ใน JWT payload";
    listPre.textContent    = "ไม่พบ personal_id ใน JWT payload";
  }

  // -------- 4) Event ปุ่ม Sync: ส่ง token + personal_id ไปให้ PHP --------
  if (btnSync) {
    btnSync.addEventListener("click", () => {
      if (!confirm("ต้องการ Sync รายชื่อบุคลากรจาก HRM หรือไม่?")) {
        return;
      }

      // สร้างฟอร์มซ่อนแล้ว submit แบบ POST
      const form = document.createElement("form");
      form.method = "POST";
      form.action = syncUrl;

      // _csrf
      const inpCsrf = document.createElement("input");
      inpCsrf.type  = "hidden";
      inpCsrf.name  = "_csrf";
      inpCsrf.value = csrfToken;
      form.appendChild(inpCsrf);

      // token
      const inpToken = document.createElement("input");
      inpToken.type  = "hidden";
      inpToken.name  = "token";
      inpToken.value = token;
      form.appendChild(inpToken);

      // personal_id (เผื่อ PHP จะใช้ filter)
      if (personalId) {
        const inpPid = document.createElement("input");
        inpPid.type  = "hidden";
        inpPid.name  = "personal_id";
        inpPid.value = personalId;
        form.appendChild(inpPid);
      }

      document.body.appendChild(form);
      form.submit();
    });
  }

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
  
  // -------- 5) เรียก API profile/list-profiles (แสดงผลบนหน้า) --------
  // ส่วนนี้เหมือนที่คุณเขียนอยู่แล้ว ผมคงโครงเดิมไว้

  try {
    const prof = await fetchJson("https://sci-sskru.com/authen/profile", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + token
      },
      body: JSON.stringify({ personal_id: personalId })
    });
    profileMeta.textContent = "สำเร็จด้วย: POST https://sci-sskru.com/authen/profile";
    show(profilePre, prof);
  } catch (e1) {
    try {
      const profGet = await fetchJson(
        "https://sci-sskru.com/authen/profile?personal_id=" + encodeURIComponent(personalId),
        { method: "GET", headers: { "Authorization": "Bearer " + token } }
      );
      profileMeta.textContent = "สำเร็จด้วย: GET https://sci-sskru.com/authen/profile?personal_id=...";
      show(profilePre, profGet);
    } catch (e2) {
      profileMeta.textContent = "เรียก profile ไม่สำเร็จ";
      profilePre.textContent  = e2.message || String(e2);
    }
  }

  try {
    const list = await fetchJson("https://sci-sskru.com/authen/list-profiles", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + token
      },
      body: JSON.stringify({ personal_id: personalId })
    });
    listMeta.textContent = "สำเร็จด้วย: POST https://sci-sskru.com/authen/list-profiles";
    show(listPre, list);
  } catch (e3) {
    try {
      const listGet = await fetchJson(
        "https://sci-sskru.com/authen/list-profiles?personal_id=" + encodeURIComponent(personalId),
        { method: "GET", headers: { "Authorization": "Bearer " + token } }
      );
      listMeta.textContent = "สำเร็จด้วย: GET https://sci-sskru.com/authen/list-profiles?personal_id=...";
      show(listPre, listGet);
    } catch (e4) {
      listMeta.textContent = "เรียก list-profiles ไม่สำเร็จ";
      listPre.textContent  = e4.message || String(e4);
    }
  }
});
</script>
