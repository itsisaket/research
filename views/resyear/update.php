<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Resyear */

$this->title = 'Update Resyear: ' . $model->resyear;
$this->params['breadcrumbs'][] = ['label' => 'Resyears', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->resyear, 'url' => ['view', 'id' => $model->resyear]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="resyear-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
