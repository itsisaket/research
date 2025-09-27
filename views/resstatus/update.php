<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Resstatus */

$this->title = 'Update Resstatus: ' . $model->statusid;
$this->params['breadcrumbs'][] = ['label' => 'Resstatuses', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->statusid, 'url' => ['view', 'id' => $model->statusid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="resstatus-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
