<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'สมัครสมาชิก';
?>
<div class="container py-5" style="max-width:620px">
  <h1 class="mb-3"><?= Html::encode($this->title) ?></h1>
  <?php $form = ActiveForm::begin(['id' => 'form-register']); ?>

  <div class="row">
    <div class="col-md-6"><?= $form->field($model, 'u_name')->textInput() ?></div>
    <div class="col-md-6"><?= $form->field($model, 'u_sname')->textInput() ?></div>
  </div>

  <?= $form->field($model, 'u_email')->textInput(['type'=>'email']) ?>
  <?= $form->field($model, 'u_tel')->textInput() ?>

  <div class="row">
    <div class="col-md-6"><?= $form->field($model, 'username')->textInput(['id'=>'reg-username']) ?></div>
    <div class="col-md-3"><?= $form->field($model, 'password')->passwordInput() ?></div>
    <div class="col-md-3"><?= $form->field($model, 'password_confirm')->passwordInput() ?></div>
  </div>

  <div class="mt-3">
    <?= Html::submitButton('สมัครสมาชิก', ['class' => 'btn btn-primary w-100']) ?>
  </div>

  <?php ActiveForm::end(); ?>
</div>

<!-- LocalStorage: พอสมัครสำเร็จ เบราว์เซอร์จะ redirect แล้ว;
     เก็บ username ตอน submit ไว้เติมอัตโนมัติในหน้า login -->
<script>
document.getElementById('form-register')?.addEventListener('submit', () => {
  const u = document.getElementById('reg-username')?.value || '';
  localStorage.setItem('last_username', u);
});
</script>
