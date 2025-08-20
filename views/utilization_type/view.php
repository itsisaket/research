<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Utilization_type */

$this->title = $model->utilization_type;
$this->params['breadcrumbs'][] = ['label' => 'Utilization Types', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="utilization-type-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'utilization_type' => $model->utilization_type], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'utilization_type' => $model->utilization_type], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'utilization_type',
            'utilization_type_name',
        ],
    ]) ?>

</div>
