<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\AcademicService */

$this->title = 'แก้ไขบริการวิชาการ';
$this->params['breadcrumbs'][] = ['label' => 'บริการวิชาการ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="academic-service-update">
  <?= $this->render('_form', ['model' => $model]) ?>
</div>
