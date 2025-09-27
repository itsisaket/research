<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrganizeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Organizes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="organize-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Organize', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'org_id',
            'org_name',
            'org_address:ntext',
            'org_tel',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
