<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\Organization;
?>

<div class="utilization-search card shadow-sm mb-3">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
        ]); ?>

        <div class="row g-3">

            <div class="col-md-5">
                <?= $form->field($model, 'project_name')
                    ->textInput(['placeholder' => 'ชื่อโครงการ']) ?>
            </div>

            <div class="col-md-3">
                <?= $form->field($model, 'username')
                    ->textInput(['placeholder' => 'username ผู้ใช้']) ?>
            </div>

            <div class="col-md-4">
                <?= $form->field($model, 'org_id')->dropDownList(
                    ArrayHelper::map(Organization::find()->all(), 'org_id', 'org_name'),
                    ['prompt' => '-- เลือกหน่วยงาน --']
                ) ?>
            </div>

        </div>

        <div class="mt-3">
            <?= Html::submitButton('🔍 ค้นหา', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('รีเซ็ต', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
