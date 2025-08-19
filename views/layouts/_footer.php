<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>
<footer class="pc-footer">
<div class="row ">
  <hr>
</div>
    <div class="row ">
      <div class="col-sm-6 ">
        <p class="m-0">
          Berry &#9829; crafted by Team
          <a href="https://themeforest.net/user/codedthemes" target="_blank" rel="noopener noreferrer">
            CodedThemes
          </a>
        </p>
      </div>
      <div class="col-sm-6 ">
        <ul class="list-inline footer-link mb-0 justify-content-sm-end d-flex">
          <li class="list-inline-item"><a href="<?= Url::to(['/site/index']) ?>">Home</a></li>
          <li class="list-inline-item"><a href="https://codedthemes.gitbook.io/berry-bootstrap/" target="_blank" rel="noopener noreferrer">Documentation</a></li>
          <li class="list-inline-item"><a href="https://codedthemes.support-hub.io/" target="_blank" rel="noopener noreferrer">Support</a></li>
        </ul>
      </div>
    </div>
</footer>
