<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\RespositionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Respositions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="resposition-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Resposition', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'res_positionid',
            'res_positionname',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
