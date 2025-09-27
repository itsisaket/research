<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Resstatus */

$this->title = 'Create Resstatus';
$this->params['breadcrumbs'][] = ['label' => 'Resstatuses', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="resstatus-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
