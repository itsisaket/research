<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

use app\models\WorkContributor;
use app\models\WorkContributorRole;

/** @var $article app\models\Article */
/** @var $isOwner bool */

$wrapCard = $wrapCard ?? true;

$refType = 'article';
$refId = (int)$article->article_id;

$contribs = WorkContributor::find()
    ->where(['ref_type' => $refType, 'ref_id' => $refId])
    ->orderBy(['sort_order' => SORT_ASC, 'wc_id' => SORT_ASC])
    ->all();

$roleItems = WorkContributorRole::items();

/* ===== เตรียม map username => "ชื่อ สกุล" (ดึงครั้งเดียว กัน N+1) ===== */
$usernames = array_values(array_unique(array_filter(array_map(function($c){ return $c->username ?? null; }, $contribs))));
$nameMap = [];
if (!empty($usernames)) {
    $rows = \app\models\Account::find()
        ->select(['username', 'uname', 'luname'])
        ->where(['username' => $usernames])
        ->asArray()
        ->all();

    foreach ($rows as $r) {
        $full = trim(($r['uname'] ?? '') . ' ' . ($r['luname'] ?? ''));
        $nameMap[$r['username']] = ($full !== '') ? $full : $r['username'];
    }
}

// รายการบุคลากรสำหรับ select2
$userItems = \app\models\Account::find()
    ->select(["CONCAT(username,' - ',uname,' ',luname) AS text"])
    ->indexBy('username')
    ->orderBy(['uname' => SORT_ASC])
    ->column();

/* ===== ฟอร์ม add ===== */
$formModel = new WorkContributor();
$formModel->scenario = 'multi';
$formModel->ref_type = $refType;
$formModel->ref_id = $refId;
$formModel->role_code_form = 'author';

// ซ่อนฟิลด์ แต่ยังส่งค่า default ให้ระบบ
$formModel->sort_order = (count($contribs) ? (count($contribs) + 1) : 1);
$formModel->note = null;
?>

<?php if ($wrapCard): ?>
<div class="card shadow-sm mt-3">
  <div class="card-body">
<?php endif; ?>

<?php if (!$wrapCard): ?>
  <div class="d-flex justify-content-between align-items-center">
    <h5 class="mb-2"><i class="fas fa-users me-1"></i> ผู้ร่วมดำเนินงาน/ผู้เขียนร่วม</h5>
    <span class="text-muted small"><?= count($contribs) ?> คน</span>
  </div>
  <hr class="mt-2 mb-3">
<?php endif; ?>
<?php if (empty($contribs)): ?>
  <div class="text-muted">ยังไม่มีผู้ร่วม</div>
<?php else: ?>

  <div class="list-group list-group-flush mb-3">

    <?php foreach ($contribs as $c): ?>
      <?php
        $uname = (string)$c->username;
        $fullName = $nameMap[$uname] ?? $uname;
        $roleText = $roleItems[$c->role_code] ?? $c->role_code;
      ?>

      <div class="list-group-item px-0">
        <div class="d-flex justify-content-between align-items-start gap-2">

          <!-- ซ้าย: ชื่อ + บทบาท -->
          <div>
            <div class="fw-semibold">
              <?= Html::encode($fullName) ?>
            </div>
            <div class="text-muted small">
              <?= Html::encode($uname) ?>
            </div>
            <span class="badge bg-secondary mt-1">
              <?= Html::encode($roleText) ?>
            </span>
          </div>

          <!-- ขวา: จัดการ -->
          <?php if ($isOwner): ?>
            <div class="text-end">
              <?= Html::a('<i class="fas fa-trash-alt"></i>', ['delete-contributor',
                  'article_id' => $refId,
                  'wc_id' => $c->wc_id
              ], [
                  'class' => 'btn btn-sm btn-outline-danger',
                  'encode' => false,
                  'data' => [
                      'confirm' => 'ลบผู้ร่วมคนนี้หรือไม่?',
                      'method' => 'post',
                  ],
              ]) ?>
            </div>
          <?php endif; ?>

        </div>
      </div>

    <?php endforeach; ?>

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
    <div class="col-12 col-md-6">
      <?= $f->field($formModel, 'role_code_form')->widget(Select2::class, [
          'data' => $roleItems,
          'options' => ['placeholder' => 'เลือกบทบาท...'],
      ])->label('บทบาท'); ?>
    </div>
  </div>

  <!-- ซ่อนเริ่มลำดับ + หมายเหตุ (ยังส่งค่าไปด้วย) -->
  <?= $f->field($formModel, 'sort_order')->hiddenInput()->label(false); ?>
  <?= $f->field($formModel, 'note')->hiddenInput()->label(false); ?>

  <div class="mt-2">
    <?= Html::submitButton('<i class="fas fa-save me-1"></i> เพิ่มผู้ร่วม', [
        'class' => 'btn btn-success',
        'encode' => false,
    ]) ?>
  </div>

  <?php ActiveForm::end(); ?>
</div>

<?php if ($wrapCard): ?>
  </div>
</div>
<?php endif; ?>
