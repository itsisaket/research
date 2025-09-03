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
    <thead>
      <tr><th>Key</th><th>Value</th></tr>
    </thead>
    <tbody id="ls-table">
      <tr><td colspan="2" class="text-center">ไม่มีข้อมูลใน localStorage</td></tr>
    </tbody>
  </table>
</div>

<!-- Profile result -->
<div class="container py-4">
  <h5>ข้อมูลผู้ใช้ (JSON จาก API <code>/authen/profile</code>)</h5>
  <pre id="profile-json" style="background:#f8f9fa; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
</div>

<!-- List profiles result -->
<div class="container py-4">
  <h5>ข้อมูลรายชื่อ (JSON จาก API <code>/authen/list-profiles</code>)</h5>
  <pre id="list-profiles-json" style="background:#f1f8ff; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
</div>

<script>
document.addEventListener("DOMContentLoaded", async () => {
  const tbody            = document.getElementById("ls-table");
  const profilePre       = document.getElementById("profile-json");
  const listProfilesPre  = document.getElementById("list-profiles-json");

  // ========== 1) แสดงค่า localStorage ==========
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

  // ========== 2) เตรียมเครื่องมือ ==========
  // decode base64url -> string
  function base64UrlDecode(str) {
    try {
      str = str.replace(/-/g, '+').replace(/_/g, '/');
      const pad = str.length % 4;
      if (pad) str += '='.repeat(4 - pad);
      const bin = atob(str);
      // พยายามถอดเป็น UTF-8 ถ้าแปลงไม่ได้ก็คืนค่าดิบ
      try {
        return decodeURIComponent(Array.from(bin).map(c =>
          '%' + c.charCodeAt(0).toString(16).padStart(2,'0')
        ).join(''));
      } catch { return bin; }
    } catch {
      return "";
    }
  }

  // parse JWT payload เป็น object
  function parseJwt(token) {
    if (!token || token.split('.').length < 2) return null;
    const payloadStr = base64UrlDecode(token.split('.')[1]);
    try { return JSON.parse(payloadStr); } catch { return null; }
  }

  // helper fetch: อ่าน error body เพื่อ debug ง่าย
  async function fetchJson(url, opts) {
    const res  = await fetch(url, opts);
    const text = await res.text();
    if (!res.ok) {
      throw new Error(`${res.status} ${res.statusText}: ${text}`);
    }
    try { return JSON.parse(text); } catch { return text; }
  }

  function show(preEl, data) {
    preEl.textContent = (typeof data === "string")
      ? data
      : JSON.stringify(data, null, 2);
  }

  // ========== 3) ดึง token จาก localStorage ==========
  const token = localStorage.getItem("hrm-sci-token");
  if (!token) {
    profilePre.textContent      = "ไม่พบ hrm-sci-token ใน localStorage";
    listProfilesPre.textContent = "ไม่พบ hrm-sci-token ใน localStorage";
    return;
  }

  // ========== 4) ดึง personal_id จาก JWT (ไม่มี fallback) ==========
  const payload = parseJwt(token) || {};
  const personalId = payload.personal_id;
  if (!personalId) {
    profilePre.textContent      = "ไม่พบ personal_id ใน payload ของ token";
    listProfilesPre.textContent = "ไม่พบ personal_id ใน payload ของ token";
    return;
  }

  // ========== 5) เรียก API จริง ==========
  try {
    // /authen/profile
    const profileData = await fetchJson("https://sci-sskru.com/authen/profile", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + token
      },
      body: JSON.stringify({ personal_id: personalId })
    });
    show(profilePre, profileData);

    // /authen/list-profiles
    const listData = await fetchJson("https://sci-sskru.com/authen/list-profiles", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + token
      },
      body: JSON.stringify({ personal_id: personalId })
    });
    show(listProfilesPre, listData);

  } catch (err) {
    const msg = (err && err.message) ? err.message : String(err);
    profilePre.textContent      = "โหลดข้อมูลไม่สำเร็จ: " + msg;
    listProfilesPre.textContent = "โหลดข้อมูลไม่สำเร็จ: " + msg;
  }
});
</script>
