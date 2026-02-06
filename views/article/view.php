<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Article */

$this->title = 'รายละเอียดการตีพิมพ์เผยแพร่';
$this->params['breadcrumbs'][] = ['label' => 'การตีพิมพ์เผยแพร่', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

/** ===== owner check: แสดง/เข้าดูได้เฉพาะเจ้าของเรื่อง (username) ===== */
$me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
$isOwner = ($me && !empty($me->username) && (string)$me->username === (string)$model->username);

if (!$isOwner) {
    throw new \yii\web\ForbiddenHttpException('คุณไม่มีสิทธิ์เข้าดูข้อมูลรายการนี้');
}

/** ===== helpers กัน null ===== */
$safe = function ($v, $fallback = '-') {
    return (isset($v) && $v !== '' && $v !== null) ? $v : $fallback;
};

?>
<div class="article-view">

  <div class="card shadow-sm mb-3">
    <div class="card-body">

      <!-- ===== Header ===== -->
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h5 class="mb-1">
            <i class="fas fa-newspaper me-1"></i> <?= Html::encode($this->title) ?>
          </h5>
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i> แสดงข้อมูลเฉพาะเจ้าของเรื่อง (username)
          </div>
        </div>
        <div class="text-muted small">
          <i class="fas fa-hashtag me-1"></i> ID: <?= Html::encode($model->article_id) ?>
        </div>
      </div>

      <!-- ===== Actions: ซ้าย (ย้อนกลับ+แก้ไข) | ขวา (ลบ) ===== -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
          <?= Html::a('<i class="fas fa-arrow-left me-1"></i> ย้อนกลับ', ['index'], [
              'class' => 'btn btn-outline-secondary',
              'encode' => false,
          ]) ?>

          <?= Html::a('<i class="fas fa-edit me-1"></i> แก้ไขข้อมูล', ['update', 'article_id' => $model->article_id], [
              'class' => 'btn btn-primary',
              'encode' => false,
          ]) ?>
        </div>

        <div>
          <?= Html::a('<i class="fas fa-trash-alt me-1"></i> ลบข้อมูล', ['delete', 'article_id' => $model->article_id], [
              'class' => 'btn btn-danger',
              'encode' => false,
              'data' => [
                  'confirm' => 'ยืนยันการลบรายการนี้หรือไม่?',
                  'method' => 'post',
              ],
          ]) ?>
        </div>
      </div>

      <!-- ===== Section: ผู้รับผิดชอบ/หน่วยงาน ===== -->
      <h5 class="mb-2"><i class="fas fa-user-tie me-1"></i> ผู้รับผิดชอบและหน่วยงาน</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'username',
                  'label' => 'นักวิจัย',
                  'value' => function ($model) use ($safe) {
                      $full = trim(($model->user->uname ?? '') . ' ' . ($model->user->luname ?? ''));
                      return $safe($full !== '' ? $full : null, $safe($model->username ?? null));
                  },
              ],
              [
                  'attribute' => 'org_id',
                  'label' => 'หน่วยงาน',
                  'value' => function ($model) use ($safe) {
                      return $safe($model->hasorg->org_name ?? null);
                  },
              ],
          ],
      ]) ?>

      <!-- ===== Section: ชื่อบทความ ===== -->
      <h5 class="mb-2"><i class="fas fa-file-alt me-1"></i> ชื่อบทความ</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'article_th',
                  'label' => 'ชื่อบทความ (ไทย)',
                  'value' => $safe($model->article_th),
              ],
              [
                  'attribute' => 'article_eng',
                  'label' => 'ชื่อบทความ (อังกฤษ)',
                  'value' => $safe($model->article_eng),
              ],
          ],
      ]) ?>

      <!-- ===== Section: ข้อมูลการเผยแพร่ ===== -->
      <h5 class="mb-2"><i class="fas fa-book me-1"></i> ข้อมูลการเผยแพร่</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-4'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'journal',
                  'label' => 'วารสาร/แหล่งเผยแพร่',
                  'value' => $safe($model->journal),
              ],
              [
                  'attribute' => 'publication_type',
                  'label' => 'ประเภทการเผยแพร่',
                  'value' => function ($model) use ($safe) {
                      return $safe($model->publi->publication_name ?? null);
                  },
              ],
              [
                  'attribute' => 'article_publish',
                  'label' => 'วันที่เผยแพร่',
                  'value' => $safe($model->article_publish),
              ],
              [
                  'attribute' => 'branch',
                  'label' => 'สาขา',
                  'value' => function ($model) use ($safe) {
                      return $safe($model->habranch->branch_name ?? null);
                  },
              ],
              [
                  'attribute' => 'status_ec',
                  'label' => 'สถานะ EC',
                  'value' => function ($model) use ($safe) {
                      return $safe($model->haec->ec_name ?? null);
                  },
              ],
          ],
      ]) ?>

      <!-- ===== Section: อ้างอิง ===== -->
      <h5 class="mb-2"><i class="fas fa-link me-1"></i> อ้างอิง/ข้อมูลเพิ่มเติม</h5>
      <hr class="mt-2 mb-3">

      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class' => 'table table-bordered table-striped mb-0'],
          'template' => '<tr><th style="width:260px;">{label}</th><td>{value}</td></tr>',
          'attributes' => [
              [
                  'attribute' => 'refer',
                  'label' => 'อ้างอิง/หมายเหตุ',
                  'format' => 'ntext',
                  'value' => $safe($model->refer),
              ],
          ],
      ]) ?>

    </div>

    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        <i class="fas fa-shield-alt me-1"></i> ระบบจำกัดสิทธิ์ตามเจ้าของรายการ (username)
      </div>
      <div class="text-muted small">
        <i class="fas fa-clock me-1"></i> <?= date('d/m/Y H:i') ?>
      </div>
    </div>

  </div>

</div>
