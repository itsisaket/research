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
    ->select(["CONCAT(uname,' ',luname) AS text"])
    ->indexBy('username')
    ->orderBy(['uname' => SORT_ASC])
    ->column();

/* ===== ฟอร์ม add ===== */
$formModel = new WorkContributor();
$formModel->scenario = 'multi';
$formModel->ref_type = $refType;
$formModel->ref_id = $refId;
$formModel->role_code_form = 'author';
$formModel->contribution_pct = null;

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
  <div class="d-flex justify-content-between align-items-center">

    <!-- ซ้าย: บรรทัดเดียว -->
    <div class="text-truncate">
      <span class="fw-semibold">
        <?= Html::encode($fullName) ?>
      </span>
      <span class="text-muted small">
        (<?= Html::encode($uname) ?>)
      </span>
      <span class="badge bg-secondary ms-1">
        <?= Html::encode($roleText) ?>
      </span>
      <?php if ($c->contribution_pct !== null && $c->contribution_pct !== ''): ?>
        <span class="badge bg-light text-dark border ms-1">
            <?= Html::encode(number_format((float)$c->contribution_pct, 0)) ?>%
        </span>
        <?php endif; ?>
        <?php if ($isOwner): ?>
        <form method="post" action="<?= \yii\helpers\Url::to(['update-contributor-pct','article_id'=>$refId,'wc_id'=>$c->wc_id]) ?>" class="d-flex align-items-center gap-1">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <input type="number" name="pct" step="0.01" min="0" max="100"
                value="<?= Html::encode($c->contribution_pct) ?>"
                class="form-control form-control-sm" style="width:90px;" placeholder="%">
            <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-check"></i></button>
        </form>
        <?php endif; ?>

    </div>

    <!-- ขวา: จัดการ -->
    <?php if ($isOwner): ?>
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
    <?php endif; ?>

  </div>
</div>


    <?php endforeach; ?>

  </div>

<?php endif; ?>


<div class="border rounded p-3 bg-light">

  <div class="fw-semibold mb-2">
    <i class="fas fa-plus-circle me-1"></i> เพิ่มผู้ร่วม
  </div>

  <?php $f = ActiveForm::begin([
      'action' => ['add-contributors', 'article_id' => $refId],
      'method' => 'post',
  ]); ?>

  <div class="d-flex flex-wrap align-items-center gap-2">

    <!-- ผู้ร่วม (กินพื้นที่หลัก) -->
    <div class="flex-grow-1" style="min-width:240px;">
      <?= $f->field($formModel, 'usernames')->widget(Select2::class, [
          'data' => $userItems,
          'options' => [
              'placeholder' => 'เลือกผู้ร่วม...',
              'multiple' => true,
          ],
          'pluginOptions' => [
              'allowClear' => true,
              'closeOnSelect' => false,
          ],
      ])->label(false); ?>
    </div>

    <!-- บทบาท -->
    <div style="min-width:160px;">
      <?= $f->field($formModel, 'role_code_form')->widget(Select2::class, [
          'data' => $roleItems,
          'options' => ['placeholder' => 'บทบาท'],
      ])->label(false); ?>
    </div>
    <div style="width:110px; min-width:110px;">
    <?= $f->field($formModel, 'pct_form')->input('number', [
        'min' => 0, 'max' => 100, 'step' => 0.01,
        'placeholder' => '%',
    ])->label(false); ?>
    </div>
    <!-- ปุ่มบันทึก -->
    <div class="align-self-end mb-2">
      <?= Html::submitButton(
          '<i class="fas fa-save me-1"></i> เพิ่ม',
          ['class' => 'btn btn-success', 'encode' => false]
      ) ?>
    </div>

  </div>

  <!-- hidden fields -->
  <?= $f->field($formModel, 'sort_order')->hiddenInput()->label(false); ?>
  <?= $f->field($formModel, 'note')->hiddenInput()->label(false); ?>
  <?= $f->field($formModel, 'contribution_pct')->hiddenInput()->label(false); ?>

  <?php ActiveForm::end(); ?>
</div>


<?php if ($wrapCard): ?>
  </div>
</div>
<?php endif; ?>
