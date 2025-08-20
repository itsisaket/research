<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ResstatusSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Resstatuses';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="resstatus-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Resstatus', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'statusid',
            'statusname',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
