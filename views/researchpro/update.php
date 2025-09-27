<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Researchpro */

$this->title = 'โครงการวิจัย';
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="researchpro-update">

<?= Html::a('<i class="glyphicon glyphicon-edit"></i> ย้อนกลับ ', ['view', 'projectID' => $model->projectID], ['class' => 'btn btn-info']) ?>
    <?= $this->render('_form', [
        'model' => $model,'amphur'=> $amphur, 'sub_district' =>$sub_district
    ]) ?>

</div>
