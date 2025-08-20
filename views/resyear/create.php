<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Resyear */

$this->title = 'Create Resyear';
$this->params['breadcrumbs'][] = ['label' => 'Resyears', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="resyear-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
