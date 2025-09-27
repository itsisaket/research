<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/** @var $model app\models\LoginForm */

$this->title = 'เข้าสู่ระบบ';
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>กรอกชื่อผู้ใช้และรหัสผ่านตาม API</p>

    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

    <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>
    <?= $form->field($model, 'password')->passwordInput() ?>
    <?= $form->field($model, 'rememberMe')->checkbox() ?>

    <div class="form-group">
        <div>
            <?= Html::submitButton('เข้าสู่ระบบ', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
