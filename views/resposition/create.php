<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Resposition */

$this->title = 'Create Resposition';
$this->params['breadcrumbs'][] = ['label' => 'Respositions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="resposition-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
