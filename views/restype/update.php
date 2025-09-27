<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Restype */

$this->title = 'Update Restype: ' . $model->restypeid;
$this->params['breadcrumbs'][] = ['label' => 'Restypes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->restypeid, 'url' => ['view', 'id' => $model->restypeid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="restype-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
