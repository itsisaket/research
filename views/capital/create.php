<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Capital */

$this->title = 'Create Capital';
$this->params['breadcrumbs'][] = ['label' => 'Capitals', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="capital-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
