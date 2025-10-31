<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ResearchproSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'โครงการวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="researchpro-index">

    <p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= $this->render('_search', ['model' => $searchModel]); ?>

    <?php Pjax::begin(['id' => 'pjax-researchpro']); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        // ถ้าคุณมี search model แบบเต็ม ให้เปิดคอมเมนต์นี้ได้
        // 'filterModel' => $searchModel,
        'columns' => [
            [
                'label' => 'การจัดการ',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a('จัดการข้อมูล', ['view', 'projectID' => $model->projectID], [
                        'class' => 'btn btn-secondary btn-sm'
                    ]);
                },
            ],
            [
                'attribute' => 'projectNameTH',
                'value' => function ($model) {
                    return $model->projectNameTH;
                },
            ],
            [
                'attribute' => 'fundingAgencyID',
                'label' => 'หน่วยงานทุน',
                'value' => function ($model) {
                    // ป้องกัน null
                    return $model->agencys->fundingAgencyName ?? '-';
                },
            ],
            [
                'attribute' => 'projectYearsubmit',
                'label' => 'ปีเสนอ',
                'value' => function ($model) {
                    return $model->projectYearsubmit ?: '-';
                },
            ],
            [
                'attribute' => 'org_id',
                'label' => 'หน่วยงาน',
                'value' => function ($model) {
                    return $model->hasorg->org_name ?? '-';
                },
            ],
            [
                'attribute' => 'uid',
                'label' => 'นักวิจัย',
                'value' => function ($model) {
                    if ($model->user) {
                        return trim(($model->user->uname ?? '') . ' ' . ($model->user->luname ?? ''));
                    }
                    return '-';
                },
            ],
            // ถ้าจะเพิ่มปุ่มมาตรฐาน (view/update/delete) ใช้อันนี้ได้
            // ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
