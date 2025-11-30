<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;

$this->title = 'จัดการบัญชีผู้ใช้';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="account-index">
  <h1><?= Html::encode($this->title) ?></h1>

  <div class="panel panel-default">
    <div class="panel-body">
        <?php Pjax::begin(['id' => 'grid-user-pjax','timeout'=>5000]) ?>

        <!-- เรียก view _search.php -->
        <?= $this->render('_search', ['model' => $searchModel]); ?>
        <hr>

        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'label' => 'ชื่อ - สกุล',
                    'value' => function($model){
                        $prefix = $model->hasprefix ? $model->hasprefix->prefixname : '';
                        $full   = trim($prefix.' '.$model->uname.' '.$model->luname);
                        return $full !== '' ? $full : '-';
                    }
                ],
                [
                    'label' => 'สังกัด',
                    'value' => function($model){
                        return $model->hasorg ? $model->hasorg->org_name : '-';
                    }
                ],
                [
                    'label' => 'สถานะ',
                    'value' => function($model){
                        return $model->hasposition ? $model->hasposition->positionname : '-';
                    }
                ],

                // ปุ่มเฉพาะ admin 
                [
                    'format' => 'raw',
                    'value' => function($model){
                        $identity = Yii::$app->user->identity;

                        // ยังไม่ได้ล็อกอิน หรือ identity ไม่ใช่ Account → ไม่โชว์ปุ่ม
                        if (!$identity instanceof \app\models\Account) {
                            return null;
                        }

                        // กำหนดว่า admin = position = 4 (ตามที่คุณใช้ใน HanumanRule)
                        $isAdmin = ((int)$identity->position === 4);

                        if (!$isAdmin) {
                            // ไม่ใช่ admin → ดูได้อย่างเดียว ไม่ให้แก้ไข/ลบ
                            return null;
                        }

                        // ✅ เฉพาะ admin แสดงปุ่มแก้ไข/ลบ
                        return
                            Html::a(
                                '<i class="fa fa-edit"></i> แก้ไข',
                                ['update', 'id' => $model->uid],
                                ['class' => 'btn btn-warning btn-sm']
                            )
                            . ' ' .
                            Html::a(
                                '<i class="fa fa-trash"></i> ลบ',
                                ['delete', 'id' => $model->uid],
                                [
                                    'class' => 'btn btn-danger btn-sm',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to delete this item?',
                                        'method'  => 'post',
                                    ],
                                ]
                            );
                    }
                ],
            ],
        ]); ?>

        <?php Pjax::end() ?>
    </div>
  </div>
</div>
