<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ResearchImportForm */

$this->title = 'นำเข้าข้อมูลงานวิจัยจาก Excel';
$this->params['breadcrumbs'][] = ['label' => 'ข้อมูลงานวิจัย', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="researchpro-import">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>กรุณาเลือกไฟล์ Excel (*.xls, *.xlsx) ที่จัดรูปแบบคอลัมน์ให้ตรงกับระบบ</p>

    <div class="row">
        <div class="col-md-6">

            <?php $form = ActiveForm::begin([
                'options' => ['enctype' => 'multipart/form-data'],
            ]); ?>

            <?= $form->field($model, 'file')->fileInput() ?>

            <div class="form-group">
                <?= Html::submitButton('นำเข้าข้อมูล', ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>
