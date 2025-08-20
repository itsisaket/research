<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Resposition */

$this->title = 'Update Resposition: ' . $model->res_positionid;
$this->params['breadcrumbs'][] = ['label' => 'Respositions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->res_positionid, 'url' => ['view', 'id' => $model->res_positionid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="resposition-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
