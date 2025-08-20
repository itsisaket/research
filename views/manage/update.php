<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Manage */

$this->title = 'Update Manage: ' . $model->manageid;
$this->params['breadcrumbs'][] = ['label' => 'Manages', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->manageid, 'url' => ['view', 'id' => $model->manageid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="manage-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
