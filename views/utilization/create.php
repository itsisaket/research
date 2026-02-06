<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization */

$this->title = 'การนำไปใช้ประโยชน์';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="utilization-create">

    <?= $this->render('_form', [
        'model'       => $model,
        'amphur'      => [],
        'subdistrict' => [],
    ]) ?>

</div>