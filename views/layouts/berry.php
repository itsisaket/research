<?php

use app\assets\BerryAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

BerryAsset::register($this);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title>ระบบจัดการวิจัย LASC SSKRU</title>
    <?php $this->head() ?>
</head>
<body data-pc-preset="preset-1">
<?php $this->beginBody() ?>

<!-- ✅ Include Navbar & Sidebar -->
<?= $this->render('_sidebar') ?>
<?= $this->render('_navbar') ?>

<!-- ✅ Main Content Area -->
<div class="pc-container">
    <div class="pc-content">
        <?= $content ?>
    </div>
</div>

<!-- ✅ Footer -->
    <?= $this->render('_footer') ?>


<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
