<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization_type */

$this->title = 'Create Utilization Type';
$this->params['breadcrumbs'][] = ['label' => 'Utilization Types', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="utilization-type-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
