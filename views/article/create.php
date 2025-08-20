<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Article */

$this->title = 'การตีพิมพ์เผยแพร่';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="article-create">


    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
