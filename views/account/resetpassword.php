<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\models\Account;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Account */

$this->title = 'เปลี่ยนรหัสผ่าน Username : ' . $model->username;
$this->params['breadcrumbs'][] = 'Resetpassword';
?>

<div class="account-Resetpassword">
    <h3><?= Html::encode($this->title) ?></h3>

    <div class="row">
        <div class="col-lg-12">
            <?php $form = ActiveForm::begin(['id' => 'account-Resetpassword']); ?>
                <div class="col-lg-6">
                    <?php
                        echo $form->field($model, 'password')->passwordInput(['maxlength' => true]);  
                        echo Html::submitButton('Reset Password', ['class' => 'btn btn-primary', 'name' => 'update-button']); 
                    ?>                    
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
