<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Academic */

$this->title = 'Update Academic: ' . $model->academicid;
$this->params['breadcrumbs'][] = ['label' => 'Academics', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->academicid, 'url' => ['view', 'id' => $model->academicid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="academic-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
