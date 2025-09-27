<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Capital */

$this->title = 'Update Capital: ' . $model->capitalid;
$this->params['breadcrumbs'][] = ['label' => 'Capitals', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->capitalid, 'url' => ['view', 'id' => $model->capitalid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="capital-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
