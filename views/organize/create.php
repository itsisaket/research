<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Organize */

$this->title = 'Create Organize';
$this->params['breadcrumbs'][] = ['label' => 'Organizes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="organize-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
