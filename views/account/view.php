<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Account */
/* @var $cntResearch int */
/* @var $cntArticle int */
/* @var $cntUtil int */
/* @var $cntService int */
/* @var $researchLatest app\models\Researchpro[] */
/* @var $articleLatest app\models\Article[] */
/* @var $utilLatest app\models\Utilization[] */
/* @var $serviceLatest app\models\AcademicService[] */

$this->title = 'โปรไฟล์ผู้ใช้';
$this->params['breadcrumbs'][] = ['label' => 'Accounts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// helper สร้างการ์ดรายการ
$listCard = function($title, $icon, $items, $viewRoute, $pkField, $titleField){
    $html = "<div class='card border-0 shadow-sm h-100'>
        <div class='card-header bg-white d-flex justify-content-between align-items-center'>
            <strong><i class='{$icon}'></i> {$title}</strong>
            <span class='text-muted small'>ล่าสุด 10 รายการ</span>
        </div>
        <div class='card-body p-0'>";

    if (empty($items)) {
        $html .= "<div class='p-3 text-muted small'>ไม่มีข้อมูล</div>";
    } else {
        $html .= "<ul class='list-group list-group-flush'>";
        foreach ($items as $m) {
            $id = $m->$pkField ?? null;
            $name = $m->$titleField ?? '-';

            if (!$id) {
                $html .= "<li class='list-group-item py-2 text-muted'>".Html::encode($name)."</li>";
                continue;
            }

            $label = "<span class='text-truncate d-inline-block' style='max-width:92%;'>"
                .Html::encode($name).
                "</span>";

            $html .= "<li class='list-group-item py-2 d-flex justify-content-between align-items-center'>"
                . Html::a($label, [$viewRoute, 'id' => $id], [
                    'class' => 'text-decoration-none',
                    'data-pjax' => 0,
                ])
                . "<span class='text-muted'><i class='bi bi-box-arrow-up-right'></i></span>
            </li>";
        }
        $html .= "</ul>";
    }

    $html .= "</div></div>";
    return $html;
};
?>

<div class="container-fluid">

  <!-- 1) สรุปจำนวนเรื่อง -->
  <div class="row mb-3">
    <div class="col-md-3 mb-2">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small text-muted">งานวิจัย</div>
          <div class="h4 mb-0"><?= (int)$cntResearch ?></div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-2">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small text-muted">บทความ</div>
          <div class="h4 mb-0"><?= (int)$cntArticle ?></div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-2">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small text-muted">การนำไปใช้</div>
          <div class="h4 mb-0"><?= (int)$cntUtil ?></div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-2">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small text-muted">บริการวิชาการ</div>
          <div class="h4 mb-0"><?= (int)$cntService ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2) ข้อมูลผู้ใช้ -->
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-white"><strong>ข้อมูลผู้ใช้</strong></div>
    <div class="card-body">
      <?= DetailView::widget([
          'model' => $model,
          'options' => ['class'=>'table table-sm table-striped mb-0'],
          'attributes' => [
              [
                  'label' => 'ชื่อ - สกุล',
                  'value' => trim(($model->uname ?? '').' '.($model->luname ?? '')),
              ],
              'email',
              'tel',
              [
                  'label' => 'สังกัด',
                  'value' => $model->hasorg->org_name ?? '-',
              ],
              [
                  'label' => 'สถานะ',
                  'value' => $model->hasposition->positionname ?? '-',
              ],
          ],
      ]) ?>
    </div>
  </div>

  <!-- 3) รายชื่อเรื่อง (แยกตาม Card) -->
  <div class="row mt-2">

    <div class="col-12 col-md-6 mb-3">
      <?= $listCard('งานวิจัย','bi bi-journal-text', $researchLatest ?? [], 'researchpro/view', 'research_id', 'projectNameTH') ?>
    </div>

    <div class="col-12 col-md-6 mb-3">
      <?= $listCard('บทความ','bi bi-file-earmark-text', $articleLatest ?? [], 'article/view', 'article_id', 'article_th') ?>
    </div>

    <div class="col-12 col-md-6 mb-3">
      <?= $listCard('การนำไปใช้','bi bi-lightbulb', $utilLatest ?? [], 'utilization/view', 'util_id', 'project_name') ?>
    </div>

    <div class="col-12 col-md-6 mb-3">
      <?= $listCard('บริการวิชาการ','bi bi-people', $serviceLatest ?? [], 'academic-service/view', 'service_id', 'title') ?>
    </div>

  </div>

</div>
