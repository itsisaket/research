<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Restype */

$this->title = 'Create Restype';
$this->params['breadcrumbs'][] = ['label' => 'Restypes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="restype-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
