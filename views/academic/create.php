<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Academic */

$this->title = 'Create Academic';
$this->params['breadcrumbs'][] = ['label' => 'Academics', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="academic-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
