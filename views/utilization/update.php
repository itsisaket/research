<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */

$this->title = 'การนำไปใช้ประโยชน์';

?>
<div class="utilization-update">


    <?= $this->render('_form', [
        'model' => $model,'amphur'=> $amphur, 'sub_district' =>$sub_district
    ]) ?>
</div>
