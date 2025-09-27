<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'เข้าสู่ระบบ';
?>
<div class="container py-5" style="max-width:420px">
  <h1 class="mb-3"><?= Html::encode($this->title) ?></h1>
  <p class="text-muted">กรอกชื่อผู้ใช้และรหัสผ่าน</p>

  <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
    <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'id'=>'login-username']) ?>
    <?= $form->field($model, 'password')->passwordInput(['id'=>'login-password']) ?>
    <?= $form->field($model, 'rememberMe')->checkbox(['id'=>'login-remember']) ?>

    <div class="mt-3">
      <?= Html::submitButton('เข้าสู่ระบบ', ['class' => 'btn btn-primary w-100']) ?>
    </div>

    <div class="text-center mt-3">
      ยังไม่มีบัญชี? <a href="<?= \yii\helpers\Url::to(['site/register']) ?>">สมัครสมาชิก</a>
    </div>
  <?php ActiveForm::end(); ?>
</div>

<!-- LocalStorage: จำ username + rememberMe (ไม่เก็บรหัสผ่าน!) -->
<script>
(function(){
  const LS_KEY_USER = 'last_username';
  const LS_KEY_REM  = 'last_remember';

  document.addEventListener('DOMContentLoaded', () => {
    const u = document.getElementById('login-username');
    const r = document.getElementById('login-remember');

    const savedU = localStorage.getItem(LS_KEY_USER);
    const savedR = localStorage.getItem(LS_KEY_REM);

    if (savedU) u.value = savedU;
    if (savedR !== null) r.checked = savedR === '1';

    const form = document.getElementById('login-form');
    form.addEventListener('submit', () => {
      // จำ username เสมอ (หรือจะผูกกับ checkbox อีกอันก็ได้)
      localStorage.setItem(LS_KEY_USER, u.value || '');
      // จำสถานะ rememberMe
      localStorage.setItem(LS_KEY_REM, r.checked ? '1' : '0');
    });
  });
})();
</script>
