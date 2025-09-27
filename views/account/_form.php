<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Organize;
use app\models\Position;
use app\models\Academic;
use yii\helpers\ArrayHelper;
/* @var $this yii\web\View */
/* @var $model app\models\Account */
/* @var $form yii\widgets\ActiveForm */
?>
<style>
        .p1 {
                font-family: "prompt", serif;
                font-size: 30px;
                font-weight: bold;
                margin-top: 15px;

                text-align: center;
        }

        .h-card {
                font-family: "prompt", serif;
                font-size: 18px;
        }
</style>
<div class="account-form">

        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
                <div class="card col">
                        <div class="card-header bg-primary">
                                <p style="margin-top: 12px" class=" h-card">กรุณากรอกข้อมูลผู้ใช้</p>
                        </div>
                        <div class="card-body login-card-body">
                                <div class="row">
                                        <div class="col-sm-2">
                                                <?= $form->field($model, 'prefix')->radioList(array('1' => 'ชาย', '2' => 'หญิง')); ?>
                                        </div>
                                        <div class="col">
                                                <?= $form->field($model, 'uname')->textInput(['maxlength' => true]) ?>

                                        </div>
                                        <div class="col">
                                                <?= $form->field($model, 'luname')->textInput(['maxlength' => true]) ?>
                                        </div>
                                </div>
                                <div class="row">
                                        <div class="col">
                                                <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
                                        </div>
                                        <div class="col">
                                                <?= $form->field($model, 'tel')->textInput() ?>
                                        </div>
                                </div>
                                <div class="row">
                                        <div class="col">
                                                <?= $form->field($model, 'org_id')->dropDownList($model->orgid, ['prompt' => 'เลือกหน่วยงาน..']) ?>
                                        </div>
                                        <div class="col">
                                                <?= $form->field($model, 'position')->dropDownList($model->positions, ['prompt' => 'เลือกสถานะ..']) ?>
                                        </div>
                                </div>
                                <?php if (Yii::$app->user->identity->position==4 or Yii::$app->user->identity->uid==$model->uid) { ?>
                                        <div class="card">
                                                <div class="card-header bg-danger">
                                                        <p style="margin-top: 12px" class=" h-card">ตั้งขื่อผู้ใช้และรหัสผ่าน</p>
                                                </div>
                                                <div class="card-body">
                                                        <div class="col">
                                                                <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
                                                        </div>
                                                        <div class="col">
                                                                <?= $form->field($model, 'password')->passwordInput(['value' => '','maxlength' => true]) ?>
                                                        </div>
                                                </div>

                                        </div>
                                <?php } ?>


                        </div>
                </div>
                <?= $form->field($model, 'password_reset_token')->hiddenInput(['value' => 'rdi00reset00token', 'readonly' => true])->label(false) ?>
                <?= $form->field($model, 'authKey')->hiddenInput(['value' => 'rdi', 'readonly' => true])->label(false) ?>

        </div>
        <hr>
        <div class="row">
                <div class="col  d-flex justify-content-center">
                        <?= Html::submitButton('ลงทะเบียน', ['class' => 'btn btn-success']) ?>
                </div>
        </div>

        <?php ActiveForm::end(); ?>

</div>