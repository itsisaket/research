<?php
use yii\helpers\Html;

$this->title = 'Contact (LocalStorage)';
?>
<div class="container py-4">
  <h1><?= Html::encode($this->title) ?></h1>

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

<script>
document.addEventListener("DOMContentLoaded", () => {
  const tbody = document.getElementById("ls-table");
  tbody.innerHTML = ""; // เคลียร์ข้อความเดิม

  if (localStorage.length === 0) {
    tbody.innerHTML = "<tr><td colspan='2' class='text-center'>ไม่มีข้อมูล</td></tr>";
    return;
  }

  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    const value = localStorage.getItem(key);
    const row = `<tr><td>${key}</td><td>${value}</td></tr>`;
    tbody.insertAdjacentHTML("beforeend", row);
  }
});
</script>
