

<?php
use yii\helpers\Html;
use yii\helpers\Url;

$appName = 'Research Information System (RIS)';
$year    = date('Y');
?>
<footer class="pc-footer">
  <div class="row">
    <hr class="my-3">
  </div>

  <div class="row align-items-center">
    <div class="col-sm-6">
      <p class="m-0">
        &copy; <?= Html::encode($year) ?>
        <?= Html::encode($appName) ?>
      </p>
    </div>

    <div class="col-sm-6">
      <ul class="list-inline footer-link mb-0 justify-content-sm-end d-flex">
        <li class="list-inline-item">
          <?= Html::a(
            'Faculty of Liberal Arts and Sciences ::',
            Url::to(['/site/index']),          // ✅ ลิงก์ภายในระบบ
            ['data-pjax' => '0']
          ) ?>
        </li>
      </ul>
    </div>
  </div>
</footer>
