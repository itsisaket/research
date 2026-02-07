<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

use app\models\Account;        // หัวหน้าโครงการ (username)
use app\models\ResearchFund;   // แหล่งทุน
use app\models\ResearchType;   // ประเภทการวิจัย

/* @var $this yii\web\View */
/* @var $model app\models\ResearchproSearch */
/* @var $form yii\widgets\ActiveForm */

$yearItems = [];
$yNow = (int)date('Y') + 543; // พ.ศ.
for ($y = $yNow; $y >= $yNow - 10; $y--) {
    $yearItems[$y] = $y;
}

$fundItems = ArrayHelper::map(ResearchFund::find()->orderBy(['researchFundName' => SORT_ASC])->all(), 'researchFundID', 'researchFundName');
$typeItems = ArrayHelper::map(ResearchType::find()->orderBy(['researchTypeName' => SORT_ASC])->all(), 'researchTypeID', 'researchTypeName');

// ถ้าคุณมีตารางบุคลากร/บัญชีผู้ใช้
$userItems = ArrayHelper::map(Account::find()->orderBy(['fullname' => SORT_ASC])->all(), 'username', 'fullname');
?>

<div class="researchpro-search card shadow-sm mb-3">
    <div class="card-body">

        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
        ]); ?>

        <div class="row g-3">

            <!-- 1) ชื่อโครงการ -->
            <div class="col-12 col-md-5">
                <?= $form->field($model, 'projectNameTH')
                    ->textInput(['placeholder' => 'ชื่อโครงการ (TH)']) ?>
            </div>

            <!-- 2) หัวหน้าโครงการ -->
            <div class="col-12 col-md-3">
                <?= $form->field($model, 'username')->dropDownList(
                    $userItems,
                    ['prompt' => '-- เลือกหัวหน้าโครงการ --']
                ) ?>
            </div>

            <!-- 3) ปีเสนอ -->
            <div class="col-12 col-md-2">
                <?= $form->field($model, 'projectYearsubmit')->dropDownList(
                    $yearItems,
                    ['prompt' => '-- ปีเสนอ --']
                ) ?>
            </div>

            <!-- 4) แหล่งทุน -->
            <div class="col-12 col-md-2">
                <?= $form->field($model, 'researchFundID')->dropDownList(
                    $fundItems,
                    ['prompt' => '-- แหล่งทุน --']
                ) ?>
            </div>

            <!-- 5) ประเภทการวิจัย -->
            <div class="col-12 col-md-3">
                <?= $form->field($model, 'researchTypeID')->dropDownList(
                    $typeItems,
                    ['prompt' => '-- ประเภทการวิจัย --']
                ) ?>
            </div>

        </div>

        <div class="mt-3">
            <?= Html::submitButton('🔍 ค้นหา', ['class' => 'btn btn-primary']) ?>
            <?= Html::resetButton('รีเซ็ต', ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
