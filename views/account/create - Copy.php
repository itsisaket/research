<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\models\Organization;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

$this->title = 'Signup';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-signup">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please fill out the following fields to signup:</p>

    <div class="row">
        <div class="col-lg-12">
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>
                <div class="col-lg-6">
                    <?= $form->field($model, 'username') ?>
                    <?= $form->field($model, 'password')->passwordInput() ?>
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-6">
                    <?= $form->field($model, 'userprefix_name')->inline(true)->radioList(array('1' => 'ชาย', '2' => 'หญิง'));?>
                    <?= $form->field($model, 'uname')->textInput(['maxlength' => true]) ?>
                    <?= $form->field($model, 'luname')->textInput(['maxlength' => true]) ?>
                    <?php 
                        $countries=Organization::find()->all();
                        $listData=ArrayHelper::map($countries,'id','name'); 
                        echo $form->field($model, 'org_id')->dropDownList($listData);
                    ?>
                        <?= Html::submitButton('Signup', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
