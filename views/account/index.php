<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'จัดการบัญชีผู้ใช้';
$this->params['breadcrumbs'][] = $this->title;

$identity = Yii::$app->user->identity;
$isAdmin = ($identity instanceof \app\models\Account) && ((int)$identity->position === 4);

/* @var $profileMap array */
$profileMap = $profileMap ?? [];

?>
<div class="account-index container-fluid">

  <!-- ===== Header ===== -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h3 class="mb-0"><?= Html::encode($this->title) ?></h3>
      <div class="text-muted small">
        จัดการข้อมูลบัญชีผู้ใช้ และตรวจสอบหน่วยงาน/สถานะการใช้งาน
      </div>
    </div>

    <div class="d-flex gap-2">
      <?= Html::a('<i class="bi bi-arrow-clockwise"></i> รีเฟรช', ['index'], [
          'class' => 'btn btn-outline-secondary',
          'encode' => false,
          'data-pjax' => 1,
      ]) ?>
      <?php if ($isAdmin): ?>
        <?= Html::a('<i class="bi bi-person-plus"></i> เพิ่มผู้ใช้', ['create'], [
            'class' => 'btn btn-primary',
            'encode' => false,
        ]) ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">

      <?php Pjax::begin(['id' => 'grid-user-pjax','timeout'=>5000]) ?>

      <!-- ===== Search ===== -->
      <div class="mb-3">
        <?= $this->render('_search', ['model' => $searchModel]); ?>
      </div>

      <div class="table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
            'layout' => "{items}\n<div class='d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3'>{summary}{pager}</div>",
            'summaryOptions' => ['class' => 'text-muted small'],
            'pager' => [
                'options' => ['class' => 'pagination pagination-sm mb-0'],
            ],
            'columns' => [
                [
                    'header' => '#',
                    'contentOptions' => ['style' => 'width:70px; white-space:nowrap;'],
                    'value' => function($model, $key, $index) use ($dataProvider){
                        $pagination = $dataProvider->pagination;
                        $start = $pagination ? $pagination->getOffset() : 0;
                        return $start + $index + 1;
                    }
                ],
                
                [
                'attribute' => 'uname',   // ✅ ทำให้คลิกหัวตารางเพื่อ sort ได้
                'label' => 'ชื่อ - สกุล',
                'format' => 'raw',
                'value' => function($model) use ($profileMap){
                    $p = $profileMap[$model->username] ?? null;

                    $academic = '';
                    $name = '';

                    if (is_array($p)) {
                        $academic = trim((string)($p['academic_type_name'] ?? ''));
                        $name     = trim(($p['first_name'] ?? '').' '.($p['last_name'] ?? ''));
                    }

                    $full = ($name !== '') ? trim($academic.' '.$name) : '';

                    // fallback ใช้ uname + luname
                    if ($full === '') {
                        $name2 = trim($model->uname.' '.$model->luname);
                        $full  = $name2 !== '' ? $name2 : '-';
                    }

                    return Html::a(Html::encode($full), ['view','id'=>$model->uid], [
                        'class'=>'fw-semibold text-decoration-none',
                        'data-pjax'=>0
                    ]);
                }
              ],



                [
                  'label' => 'สังกัด',
                  'format' => 'raw',
                  'value' => function($model) use ($profileMap){
                      $p = $profileMap[$model->username] ?? null;

                      if (is_array($p)) {
                          $fac = trim((string)($p['faculty_name'] ?? ''));
                          $dep = trim((string)($p['dept_name'] ?? ''));
                          if ($fac !== '' || $dep !== '') {
                              $html = '';
                              if ($fac !== '') $html .= "<div class='fw-semibold'>".Html::encode($fac)."</div>";
                              if ($dep !== '') $html .= "<div class='text-muted small'>".Html::encode($dep)."</div>";
                              return $html;
                          }
                      }

                      $org = $model->hasorg ? $model->hasorg->org_name : '-';
                      return "<span class='text-body'>".Html::encode($org)."</span>";
                  }
                ],
                [
                    'label' => 'สถานะ',
                    'format' => 'raw',
                    'contentOptions' => ['style' => 'width:180px;'],
                    'value' => function($model){
                        $pos = $model->hasposition ? $model->hasposition->positionname : '-';

                        // ใส่ badge ให้ดูอ่านง่าย (ปรับเงื่อนไขตามระบบคุณได้)
                        $class = 'bg-secondary';
                        $p = mb_strtolower((string)$pos);
                        if (strpos($p, 'admin') !== false || strpos($p, 'ผู้ดูแล') !== false) $class = 'bg-danger';
                        elseif (strpos($p, 'ผู้ใช้งาน') !== false || strpos($p, 'user') !== false) $class = 'bg-primary';
                        elseif (strpos($p, 'ผู้ตรวจ') !== false || strpos($p, 'review') !== false) $class = 'bg-warning text-dark';

                        return "<span class='badge {$class}'>{$pos}</span>";
                    }
                ],
            ],
        ]); ?>
      </div>

      <?php Pjax::end() ?>

    </div>
  </div>
</div>
