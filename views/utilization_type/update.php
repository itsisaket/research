<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization_type */

$this->title = 'Update Utilization Type: ' . $model->utilization_type;
$this->params['breadcrumbs'][] = ['label' => 'Utilization Types', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->utilization_type, 'url' => ['view', 'utilization_type' => $model->utilization_type]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="utilization-type-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
