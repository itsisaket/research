<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Project */

$this->title = 'Create Project';
$this->params['breadcrumbs'][] = ['label' => 'Projects', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-create">
<?= Html::a('กลับหน้าหลักงานวิจัย', ['index'], ['class' => 'btn btn-info']) ?>

<?= $this->render('_form', [
        'model' => $model,
        'amphur'=> [],
        'sub_district' =>[],
    ]) ?>

</div>
