<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Article */

$this->title = 'การตีพิมพ์เผยแพร่';
?>
<div class="article-update">

<?= Html::a('<i class="glyphicon glyphicon-edit"></i> ย้อนกลับ ', ['view', 'article_id' => $model->article_id], ['class' => 'btn btn-info']) ?>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
