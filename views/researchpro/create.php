<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */

$this->title = 'โครงการวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="researchpro-create">


    <?= $this->render('_form', [
        'model' => $model,
        'amphur'=> [],
        'sub_district' =>[],
    ]) ?>

</div>
