<?php
use yii\helpers\Html;

if (!isset($profile['profile'])) {
    echo "<h3>Profile Not Found</h3>";
    return;
}

$profile = $profile['profile'];
?>

<h2>Profile Information</h2>
<p><strong>Name:</strong>
    <?= Html::encode($profile['title_name'] ?? '') ?>
    <?= Html::encode($profile['first_name'] ?? '') ?>
    <?= Html::encode($profile['last_name'] ?? '') ?>
</p>

<p><strong>Email:</strong>
    <?= Html::encode($profile['email'] ?? '-') ?>
</p>

<p><strong>Department:</strong>
    <?= Html::encode($profile['dept_name'] ?? '-') ?>
</p>
