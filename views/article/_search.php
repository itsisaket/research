<?php
use yii\helpers\ArrayHelper;


?>

<div class="article-search card shadow-sm mb-3">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
        ]); ?>

        <div class="row g-3">

            <div class="col-12 col-md-6">
                <?= $form->field($model, 'article_th')
                    ->textInput(['placeholder' => 'ชื่อบทความ (ไทย)']) ?>
            </div>

            <div class="col-12 col-md-3">
                <?= $form->field($model, 'publication_type')
                    ->dropDownList($pubItems, [
                        'prompt' => '-- ประเภทฐาน --',
                        'options' => ['' => ['selected' => true]], // ✅ ให้เริ่มต้นเป็นค่าว่าง
                    ])
                    ->label('ประเภทฐาน') ?>
            </div>

            <div class="col-12 col-md-3">
                <?= $form->field($model, 'researcher_name')
                    ->textInput(['placeholder' => 'ชื่อ/นามสกุล นักวิจัย']) ?>
            </div>

        </div>

        <div class="mt-3">
            <?= Html::submitButton('🔍 ค้นหา', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('รีเซ็ต', ['index'], ['class' => 'btn btn-outline-secondary']) ?>

        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
