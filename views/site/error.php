<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name;
?>
<div class="site-error" style="text-align:center; padding: 80px 20px; font-family: 'Prompt', sans-serif;">
    <h1 style="font-size: 80px; color: #e74c3c; margin-bottom: 10px;">403</h1>
    <h1><?= Html::encode($name) ?></h1>
    <h2 style="font-size: 28px; color: #2c3e50;">ไม่ได้รับอนุญาตให้เข้าถึงหน้านี้</h2>
    <p style="font-size: 18px; color: #7f8c8d; margin-top: 15px;">
        ขออภัย คุณไม่มีสิทธิ์ในการเข้าถึงเนื้อหาส่วนนี้ของเว็บไซต์<br>
        <?= Html::encode($message) ?>
    </p>
    <?= Html::a('กลับไปหน้าหลัก', Yii::$app->homeUrl, ['class' => 'btn btn-primary', 'style' => 'margin-top: 25px; padding: 10px 25px; font-size: 16px;']) ?>
</div>
