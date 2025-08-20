<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\Utilization_typeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Utilization Types';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="utilization-type-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Utilization Type', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'utilization_type',
            'utilization_type_name',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Utilization_type $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'utilization_type' => $model->utilization_type]);
                 }
            ],
        ],
    ]); ?>


</div>
