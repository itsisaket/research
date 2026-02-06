<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\AcademicService */

$this->title = 'เพิ่มบริการวิชาการ';
$this->params['breadcrumbs'][] = ['label' => 'บริการวิชาการ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="academic-service-create">
  <?= $this->render('_form', ['model' => $model]) ?>
</div>
