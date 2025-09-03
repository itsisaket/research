<?php
/** @var yii\web\View $this */
use yii\helpers\Html;

$this->title = 'About';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
  <h1><?= Html::encode($this->title) ?></h1>
  <p>This is the About page. You may modify the following file to customize its content:</p>
  <code><?= __FILE__ ?></code>
</div>
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

  // -------- 3) ดึง token และ personal_id จาก JWT เท่านั้น --------
  const token = localStorage.getItem("hrm-sci-token");
  if (!token){
    jwtPre.textContent     = "ไม่พบ hrm-sci-token ใน localStorage";
    profilePre.textContent = "ไม่พบ hrm-sci-token ใน localStorage";
    listPre.textContent    = "ไม่พบ hrm-sci-token ใน localStorage";
    return;
  }

  const payload = parseJwt(token) || {};
  show(jwtPre, payload);

  const personalId = payload.personal_id;
  if (!personalId){
    profilePre.textContent = "ไม่พบ personal_id ใน JWT payload";
    listPre.textContent    = "ไม่พบ personal_id ใน JWT payload";
    return;
  }

  // -------- 4) เรียก API --------
  try {
    // /authen/profile (POST)
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
    // ถ้า route นี้ไม่รับ POST ให้ลอง GET ทันที (บางระบบใช้ GET)
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

  // list-profiles: service ของคุณก่อนหน้านี้ตอบ 404 POST → ลอง GET ด้วย
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
