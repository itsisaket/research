<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;   // ✅ เพิ่มใช้ ActiveForm สำหรับฟอร์มใน Modal

/* @var $this yii\web\View */
/* @var $searchModel app\models\ResearchproSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $importModel app\models\ResearchImportForm */

$this->title = 'โครงการวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="researchpro-index">

    <p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>

        <!-- 🔵 ปุ่มเปิด Modal อัปโหลดไฟล์ Excel -->
        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#uploadModal">
            <i class="fas fa-file-upload"></i> อัปโหลดไฟล์ Excel
        </button>
    </p>

    <!-- 🔶 แสดง Flash message ทั่วไป -->
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <!-- 🔶 แสดงรายละเอียด error รายแถวจากการ import (ถ้ามี) -->
    <?php $importErrors = Yii::$app->session->getFlash('importErrors', []); ?>
    <?php if (!empty($importErrors)): ?>
        <div class="alert alert-warning">
            <strong>รายละเอียดข้อผิดพลาดจากไฟล์ Excel:</strong>
            <ul>
                <?php foreach ($importErrors as $row => $errors): ?>
                    <li>
                        แถวที่ <?= Html::encode($row) ?>:
                        <?php foreach ($errors as $attr => $msg): ?>
                            <div>- <?= Html::encode($msg) ?></div>
                        <?php endforeach; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>


    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

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
                    return Html::a('<i class="fas fa-eye"></i>', ['view', 'projectID' => $model->projectID], [
                        'class' => 'btn btn-sm btn-outline-secondary'
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
                'attribute' => 'username',
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

<!-- 🔵 Modal สำหรับอัปโหลดไฟล์ Excel -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="uploadModalLabel">อัปโหลดไฟล์ Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <?php $form = ActiveForm::begin([
            'action'  => ['import'],                         // 🔁 ส่งไป actionImport
            'options' => ['enctype' => 'multipart/form-data']
        ]); ?>

        <?= $form->field($importModel, 'file')->fileInput([
            'accept' => '.xls,.xlsx'
        ]) ?>

        <div class="form-group mt-3">
            <?= Html::submitButton('นำเข้าข้อมูล', ['class' => 'btn btn-success']) ?>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        </div>

        <?php ActiveForm::end(); ?>

      </div>
    </div>
  </div>
</div>
