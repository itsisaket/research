<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Manage */

$this->title = 'Create Manage';
$this->params['breadcrumbs'][] = ['label' => 'Manages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="manage-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
