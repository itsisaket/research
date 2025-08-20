<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Prefix */

$this->title = 'Update Prefix: ' . $model->prefixid;
$this->params['breadcrumbs'][] = ['label' => 'Prefixes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->prefixid, 'url' => ['view', 'id' => $model->prefixid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="prefix-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
