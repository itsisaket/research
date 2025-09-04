<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
$this->title = 'Login (LocalStorage)';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container py-4">
  <h1><?= Html::encode($this->title) ?></h1>
  <p class="text-muted">หน้านี้จะตรวจสอบโทเคนใน localStorage (คีย์: <code>hrm-sci-token</code>) และแสดงผลทันที</p>

  <!-- สถานะการตรวจสอบ -->
  <div id="status" class="alert alert-info">กำลังตรวจสอบ...</div>

  <!-- สรุป personal_id -->
  <div class="mt-3">
    <strong>personal_id:</strong>
    <span id="pid" class="badge bg-secondary">ยังไม่มีข้อมูล</span>
  </div>

  <!-- แสดง JWT payload -->
  <div class="mt-3">
    <h5 class="mb-2">JWT Payload (จาก <code>hrm-sci-token</code>)</h5>
    <pre id="jwt-json" style="background:#fff7e6; padding:1rem; border:1px solid #ddd;">ยังไม่มีข้อมูล</pre>
  </div>

  <!-- เครื่องมือเสริม -->
  <div class="mt-3 d-flex flex-wrap gap-2">
    <button id="btn-copy-token" class="btn btn-outline-secondary btn-sm">คัดลอก Token</button>
    <button id="btn-clear-token" class="btn btn-outline-danger btn-sm">ลบ hrm-sci-token</button>
    <a href="<?= Yii::$app->urlManager->createUrl(['/site/index']) ?>" class="btn btn-secondary btn-sm">กลับหน้าแรก</a>
  </div>
</div>

<script>
// ---- helpers: decode JWT (base64url) ----
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
  if (!token) return null;
  const parts = token.split('.');
  if (parts.length < 2) return null;
  try { return JSON.parse(b64urlDecode(parts[1])); } catch { return null; }
}

function render(){
  const statusEl = document.getElementById('status');
  const pre      = document.getElementById('jwt-json');
  const pidEl    = document.getElementById('pid');

  const token = localStorage.getItem('hrm-sci-token');

  if (!token) {
    statusEl.className = 'alert alert-warning';
    statusEl.textContent = 'ยังไม่มีข้อมูล (ไม่พบ hrm-sci-token ใน localStorage)';
    pre.textContent = 'ยังไม่มีข้อมูล';
    pidEl.textContent = 'ยังไม่มีข้อมูล';
    pidEl.className = 'badge bg-secondary';
    return;
  }

  statusEl.className = 'alert alert-success';
  statusEl.textContent = 'พบ hrm-sci-token ใน localStorage';

  const payload = parseJwt(token);
  if (!payload) {
    pre.textContent = 'พบโทเคน แต่ไม่สามารถถอดรหัส JWT payload';
    pidEl.textContent = 'ยังไม่มีข้อมูล';
    pidEl.className = 'badge bg-secondary';
    return;
  }

  // แสดง payload สวยงาม
  pre.textContent = JSON.stringify(payload, null, 2);

  // แสดง personal_id
  const pid = payload.personal_id;
  if (pid) {
    pidEl.textContent = pid;
    pidEl.className = 'badge bg-success';
  } else {
    pidEl.textContent = 'ยังไม่มีข้อมูล';
    pidEl.className = 'badge bg-secondary';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  render();

  // ปุ่มคัดลอก/ลบ
  document.getElementById('btn-copy-token').onclick = async () => {
    const token = localStorage.getItem('hrm-sci-token') || '';
    if (!token) return alert('ไม่พบ hrm-sci-token');
    try { await navigator.clipboard.writeText(token); alert('คัดลอก Token แล้ว'); }
    catch(e){ alert('คัดลอกไม่สำเร็จ: ' + e); }
  };

  document.getElementById('btn-clear-token').onclick = () => {
    if (confirm('ยืนยันการลบ hrm-sci-token ออกจาก localStorage?')) {
      localStorage.removeItem('hrm-sci-token');
      render();
    }
  };
});
</script>
