<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'จัดการบัญชีผู้ใช้';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="account-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="panel panel-default">
        <div class="panel-body">
            <?php Pjax::begin(['id' => 'grid-user-pjax','timeout'=>5000]) ?>

            <?= $this->render('_search', ['model' => $searchModel]); ?>
            <hr>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'label' => 'ชื่อ - สกุล',
                        'value'=>function($model){
                            $prefix = $model->hasprefix ? $model->hasprefix->prefixname : '';
                            $full = trim($prefix.' '.$model->uname.' '.$model->luname);
                            return $full !== '' ? $full : '-';
                        }
                    ],
                    [
                        'label' => 'อีเมล์',
                        'value'=>function($model){
                            return $model->email ?: '-';
                        }
                    ],
                    [
                        'label' => 'เบอร์ติดต่อ',
                        'value'=>function($model){
                            return $model->tel ?: '-';
                        }
                    ],
                    [
                        'label' => 'สังกัด',
                        'value'=>function($model){
                            return $model->hasorg ? $model->hasorg->org_name : '-';
                        }
                    ],
                    [
                        'label' => 'สถานะ',
                        'value'=>function($model){
                            return $model->hasposition ? $model->hasposition->positionname : '-';
                        }
                    ],

                    // ปุ่ม แนะนำให้เปิดทีหลัง เมื่อหน้าไม่ 500 แล้ว
                    /*
                    [
                        'format' => 'raw',
                        'value' => function($model){
                            return Html::a(
                                '<i class="fa fa-edit"></i> แก้ไข',
                                ['update', 'id' => $model->uid],
                                ['class' => 'btn btn-warning btn-sm']
                            );
                        }
                    ],
                    */
                ],
            ]); ?>

            <?php Pjax::end() ?>
        </div>
    </div>
</div>
