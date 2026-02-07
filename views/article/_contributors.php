<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

use app\models\WorkContributor;
use app\models\WorkContributorRole;

/** @var $article app\models\Article */
/** @var $isOwner bool */

$refType = 'article';
$refId = (int)$article->article_id;

$contribs = WorkContributor::find()
    ->where(['ref_type' => $refType, 'ref_id' => $refId])
    ->orderBy(['sort_order' => SORT_ASC, 'wc_id' => SORT_ASC])
    ->all();

$roleItems = WorkContributorRole::items();

// รายการบุคลากรสำหรับ select2
$userItems = \app\models\Account::find()
    ->select(["CONCAT(username,' - ',uname,' ',luname) AS text"])
    ->indexBy('username')
    ->orderBy(['uname' => SORT_ASC])
    ->column();

$formModel = new WorkContributor();
$formModel->scenario = 'multi';
$formModel->ref_type = $refType;
$formModel->ref_id = $refId;
$formModel->role_code_form = 'author';
$formModel->sort_order = (count($contribs) ? (count($contribs) + 1) : 1);
?>

<div class="card shadow-sm mt-3">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0"><i class="fas fa-users me-1"></i> ผู้ร่วมดำเนินงาน/ผู้เขียนร่วม</h5>
      <span class="text-muted small"><?= count($contribs) ?> คน</span>
    </div>
    <hr class="mt-2 mb-3">

    <?php if (empty($contribs)): ?>
      <div class="text-muted">ยังไม่มีผู้ร่วม</div>
    <?php else: ?>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:70px;">ลำดับ</th>
              <th>ผู้ร่วม</th>
              <th style="width:180px;">บทบาท</th>
              <?php if ($isOwner): ?>
                <th style="width:90px;">จัดการ</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($contribs as $c): ?>
            <tr>
              <td class="text-center"><?= (int)$c->sort_order ?></td>
              <td><?= Html::encode($c->username) ?></td>
              <td><?= Html::encode($roleItems[$c->role_code] ?? $c->role_code) ?></td>
              <?php if ($isOwner): ?>
                <td class="text-center">
                  <?= Html::a('<i class="fas fa-trash-alt"></i>', ['delete-contributor',
                      'article_id' => $refId,
                      'wc_id' => $c->wc_id
                  ], [
                      'class' => 'btn btn-sm btn-outline-danger',
                      'encode' => false,
                      'data' => ['confirm' => 'ลบผู้ร่วมคนนี้หรือไม่?', 'method' => 'post'],
                  ]) ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="border rounded p-3 bg-light">
      <div class="fw-semibold mb-2"><i class="fas fa-plus-circle me-1"></i> เพิ่มผู้ร่วม (เลือกได้หลายคน)</div>

      <?php $f = ActiveForm::begin([
          'action' => ['add-contributors', 'article_id' => $refId],
          'method' => 'post',
      ]); ?>

      <?= $f->field($formModel, 'usernames')->widget(Select2::class, [
          'data' => $userItems,
          'options' => ['placeholder' => 'เลือกผู้ร่วม...', 'multiple' => true],
          'pluginOptions' => ['allowClear' => true, 'closeOnSelect' => false],
      ])->label(false); ?>

      <div class="row g-2">
        <div class="col-12 col-md-4">
          <?= $f->field($formModel, 'role_code_form')->widget(Select2::class, [
              'data' => $roleItems,
              'options' => ['placeholder' => 'เลือกบทบาท...'],
          ])->label('บทบาท'); ?>
        </div>
        <div class="col-12 col-md-3">
          <?= $f->field($formModel, 'sort_order')->input('number', ['min' => 1])->label('เริ่มลำดับ'); ?>
        </div>
        <div class="col-12 col-md-5">
          <?= $f->field($formModel, 'note')->textInput(['maxlength' => true])->label('หมายเหตุ'); ?>
        </div>
      </div>

      <div class="mt-2">
        <?= Html::submitButton('<i class="fas fa-save me-1"></i> เพิ่มผู้ร่วม', [
            'class' => 'btn btn-success',
            'encode' => false,
        ]) ?>
      </div>

      <?php ActiveForm::end(); ?>
    </div>

  </div>
</div>
