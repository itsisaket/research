<div id="profile-box" class="card card-body" style="display:none"></div>
<div id="err" class="text-danger mt-2"></div>

<script>
(function(){
  const TOKEN_KEY = 'hrm-sci-token';
  const ssoLoginUrl = '<?= \yii\helpers\Url::to(Yii::$app->params['ssoLoginUrl']) ?>';
  const token = localStorage.getItem(TOKEN_KEY);

  // ถ้าไม่มี token → ส่งไปหน้า login ส่วนกลาง พร้อม redirect กลับ URL ปัจจุบัน
  if (!token) {
    const back = window.location.href;
    window.location.href = ssoLoginUrl + '?redirect=' + encodeURIComponent(back);
    return;
  }

  // decode personal_id จาก payload (ถ้าอยากแน่ใจ ส่งไปให้เซิร์ฟเวอร์ถอดก็ได้)
  function decodePayload(jwt){
    try {
      const p = jwt.split('.')[1];
      return JSON.parse(atob(p.replace(/-/g,'+').replace(/_/g,'/')));
    } catch (e) { return null; }
  }
  const claims = decodePayload(token) || {};
  const personalId = claims.personal_id || claims.uname || '';

  // เรียก proxy ฝั่งเรา ให้ไปติดต่อ POST /authen/profile (ของ SSO)
  fetch('<?= \yii\helpers\Url::to(['/auth/profile']) ?>', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ token: token, personal_id: personalId })
  })
  .then(r => r.json())
  .then(d => {
    if (!d.ok) throw new Error(d.error || 'FAILED');
    const box = document.getElementById('profile-box');
    const p = d.profile || {};
    box.style.display = 'block';
    box.innerHTML = `
      <h5 class="mb-2">ข้อมูลผู้ใช้</h5>
      <div><b>ชื่อ-สกุล:</b> ${(p.title_name||'')+' '+(p.first_name||'')+' '+(p.last_name||'')}</div>
      <div><b>Personal ID:</b> ${personalId || '-'}</div>
      <div><b>Email:</b> ${p.email || '-'}</div>
      <div><b>หน่วยงาน:</b> ${p.dept_name || '-'}</div>
    `;
  })
  .catch(err => {
    // โทเค็นไม่ถูกต้อง/หมดอายุ → ล้างแล้วเด้งไป SSO
    console.error(err);
    localStorage.removeItem(TOKEN_KEY);
    const back = window.location.href;
    window.location.href = ssoLoginUrl + '?redirect=' + encodeURIComponent(back);
  });
})();
</script>
