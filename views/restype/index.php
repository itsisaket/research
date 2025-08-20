<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\RestypeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Restypes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="restype-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Restype', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'restypeid',
            'restypename',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
