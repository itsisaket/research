<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CapitalSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Capitals';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="capital-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Capital', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'capitalid',
            'capitalname',
            'capitaltype',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
