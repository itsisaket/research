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

<div class="container py-4">
  <p class="text-muted">ข้อมูลที่บันทึกไว้ใน localStorage:</p>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Key</th>
        <th>Value</th>
      </tr>
    </thead>
    <tbody id="ls-table">
      <tr><td colspan="2" class="text-center">ไม่มีข้อมูลใน localStorage</td></tr>
    </tbody>
  </table>
</div>

<div class="container py-4">
  <h5>ข้อมูลผู้ใช้ (JSON จาก API /authen/profile)</h5>
  <pre id="profile-json" style="background:#f8f9fa; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
</div>

<div class="container py-4">
  <h5>ข้อมูลรายชื่อ (JSON จาก API /authen/list-profiles)</h5>
  <pre id="list-profiles-json" style="background:#f1f8ff; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
</div>

<script>
document.addEventListener("DOMContentLoaded", async () => {
  const tbody = document.getElementById("ls-table");
  const profileJson = document.getElementById("profile-json");
  const listProfilesJson = document.getElementById("list-profiles-json");

  tbody.innerHTML = ""; // เคลียร์ข้อความเดิม

  if (localStorage.length === 0) {
    tbody.innerHTML = "<tr><td colspan='2' class='text-center'>ไม่มีข้อมูล</td></tr>";
    return;
  }

  // แสดง localStorage ทั้งหมด
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    const value = localStorage.getItem(key);
    const row = `<tr><td>${key}</td><td>${value}</td></tr>`;
    tbody.insertAdjacentHTML("beforeend", row);
  }

  // ตรวจสอบ token
  const token = localStorage.getItem("hrm-sci-token");
  if (token) {
    try {
      // === ดึง profile ===
      const responseProfile = await fetch("https://sci-sskru.acom/authen/profile", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + token
        },
        body: JSON.stringify({
          personal_id: "1349000000011"
        })
      });

      if (!responseProfile.ok) throw new Error("API profile Error " + responseProfile.status);
      const dataProfile = await responseProfile.json();
      profileJson.textContent = JSON.stringify(dataProfile, null, 2);

      // === ดึง list-profiles ===
      const responseList = await fetch("https://sci-sskru.com/authen/list-profiles", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + token
        },
        body: JSON.stringify({
          personal_id: "3331000521623"
        })
      });

      if (!responseList.ok) throw new Error("API list-profiles Error " + responseList.status);
      const dataList = await responseList.json();
      listProfilesJson.textContent = JSON.stringify(dataList, null, 2);

    } catch (err) {
      profileJson.textContent = "โหลดข้อมูลไม่สำเร็จ: " + err.message;
      listProfilesJson.textContent = "โหลดข้อมูลไม่สำเร็จ: " + err.message;
    }
  }
});
</script>
