<?php
use yii\helpers\Url;
use yii\helpers\Json;

$ssoLoginUrl    = Yii::$app->params['ssoLoginUrl'] ?? 'https://sci-sskru.com/hrm/login';
$authProfileUrl = Url::to(['/auth/profile'], true); // absolute
$currentUrl     = Url::current([], true);

// เตรียมค่าเป็น JSON สำหรับ JS
$ssoLoginUrlJs    = Json::encode($ssoLoginUrl);
$authProfileUrlJs = Json::encode($authProfileUrl);
$currentUrlJs     = Json::encode($currentUrl);
?>
<script>
(function(){
  const TOKEN_KEY   = 'hrm-sci-token';
  const ssoLoginUrl = <?= $ssoLoginUrlJs ?>;
  const profileApi  = <?= $authProfileUrlJs ?>;
  const currentUrl  = <?= $currentUrlJs ?>;

  // กันลูป: ไม่ redirect ถ้าอยู่หน้า login/logout ของระบบเรา
  const path = location.pathname.replace(/\/+$/, '');
  if (path.endsWith('/site/login') || path.endsWith('/site/logout')) return;

  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) {
    // ไม่มี token -> ส่งไป SSO พร้อม redirect กลับมาหน้าปัจจุบัน
    location.replace(ssoLoginUrl + '?redirect=' + encodeURIComponent(currentUrl));
    return;
  }

  // decode payload เพื่อตรวจ exp และเอา personal_id
  function decodePayload(jwt){
    try{
      const p = jwt.split('.')[1]; if (!p) return null;
      return JSON.parse(atob(p.replace(/-/g,'+').replace(/_/g,'/')));
    }catch(e){ return null; }
  }
  const claims = decodePayload(token) || {};
  if (claims.exp && Date.now()/1000 >= claims.exp) {
    localStorage.removeItem(TOKEN_KEY);
    location.replace(ssoLoginUrl + '?redirect=' + encodeURIComponent(currentUrl));
    return;
  }
  const personalId = claims.personal_id || claims.uname || '';

  // เรียก proxy ของเราให้ไป POST /authen/profile ที่ SSO
  fetch(profileApi, {
    method : 'POST',
    headers: {'Content-Type':'application/json'},
    body   : JSON.stringify({ token: token, personal_id: personalId })
  })
  .then(async (r) => {
    if (!r.ok) { throw new Error('HTTP ' + r.status); }
    return r.json();
  })
  .then(d => {
    if (!d || !d.ok) throw new Error((d && d.error) || 'FAILED');

    // แสดงกล่องข้อมูลตัวอย่าง (ถ้ามี div#profile-box ในหน้า)
    const box = document.getElementById('profile-box');
    if (box) {
      const p = d.profile || {};
      box.style.display = 'block';
      box.innerHTML = [
        '<h5 class="mb-2">ข้อมูลผู้ใช้</h5>',
        '<div><b>ชื่อ-สกุล:</b> ' + [(p.title_name||''),(p.first_name||''),(p.last_name||'')].join(' ').trim() + '</div>',
        '<div><b>Personal ID:</b> ' + (personalId || '-') + '</div>',
        '<div><b>Email:</b> ' + (p.email || '-') + '</div>',
        '<div><b>หน่วยงาน:</b> ' + (p.dept_name || '-') + '</div>'
      ].join('');
    }

    // อัปเดตชื่อ/บทบาท/รูปบน navbar (ถ้ามี id เหล่านี้ใน DOM)
    const nameEl = document.getElementById('nav-display-name');
    const roleEl = document.getElementById('nav-role');
    const avatar = document.getElementById('nav-avatar');

    if (nameEl) {
      const fullName = ['คุณ', (d.profile?.title_name||''), (d.profile?.first_name||''), (d.profile?.last_name||'')].join(' ').replace(/\s+/g,' ').trim();
      if (fullName !== 'คุณ') nameEl.textContent = fullName;
    }
    if (roleEl) {
      const role = d.profile?.academic_type_name || d.profile?.employee_type_name || d.profile?.category_type_name || '';
      roleEl.textContent = role;
      roleEl.style.display = role ? '' : 'none';
    }
    if (avatar) {
      const imgRaw = d.profile?.img || '';
      let imgUrl = avatar.getAttribute('data-fallback') || avatar.src;
      if (imgRaw) {
        if (/^https?:\/\//i.test(imgRaw)) {
          imgUrl = imgRaw;
        } else {
          imgUrl = 'https://sci-sskru.com/authen/' + String(imgRaw).replace(/^\/+/, '');
        }
      }
      const v = d.profile?.updated_at ? String(d.profile.updated_at) : (claims.iat ? String(parseInt(claims.iat)) : '');
      if (v) imgUrl += (imgUrl.includes('?') ? '&' : '?') + 'v=' + encodeURIComponent(v);
      avatar.src = imgUrl;
    }
  })
  .catch(err => {
    console.error('[navbar profile] ', err);
    localStorage.removeItem(TOKEN_KEY);
    location.replace(ssoLoginUrl + '?redirect=' + encodeURIComponent(currentUrl));
  });

  // ลบ token ตอนกด Logout (ปุ่ม submit ที่มี data-action="logout")
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-action="logout"]');
    if (btn) { try { localStorage.removeItem(TOKEN_KEY); } catch(_){} }
  });
})();
</script>
