<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Organize */

$this->title = 'Update Organize: ' . $model->org_id;
$this->params['breadcrumbs'][] = ['label' => 'Organizes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->org_id, 'url' => ['view', 'id' => $model->org_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="organize-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
