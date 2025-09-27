<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap4\Modal;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ResearchproSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'โครงการวิจัย';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="researchpro-index">

<p>
        <?= Html::a('เพิ่มข้อมูล', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php  echo $this->render('_search', ['model' => $searchModel]); ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
          'attribute' => 'การจัดการ',
          'format'=>'raw',
          'value'=>function($model){
            return  Html::a('จัดการข้อมูล', ['view', 'projectID' => $model->projectID], ['class' => 'btn btn-secondary']);     
          }
        ],          
        [
            'attribute' => 'projectNameTH',
            'value'=>function($model){
              return $model->projectNameTH;
            }
          ],
          [
            'attribute' => 'fundingAgencyID',
            'value'=>function($model){
              return $model->agencys->fundingAgencyName;
            }
          ],
          [
            'attribute' => 'projectYearsubmit',
            'value'=>function($model){
              return $model->projectYearsubmit;
            }
          ], 
          [
            'attribute' => 'org_id',
            'value'=>function($model){
              return $model->hasorg->org_name;
            }
          ], 
          [
            'attribute' => 'uid',
            'value'=>function($model){
              return $model->user->uname.' '.$model->user->luname;
            }
          ],            

            

    ],
]); ?>


</div>
