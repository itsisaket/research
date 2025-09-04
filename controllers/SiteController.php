<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

use app\models\Organize;
use app\models\Researchpro;
use app\models\Account;
use app\models\Resyear;
use app\models\Article;
use app\models\Utilization;
use app\models\ResGency;
use app\models\Publication;



class SiteController extends Controller
{
    public function beforeAction($action)
    {
        if ($action->id === 'login-bind') {   // ← เดิม sso-bind
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout','login','login-bind'],
                'rules' => [
                    ['actions' => ['login','login-bind'], 'allow' => true],
                    ['actions' => ['index','logout'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout'     => ['post','get'],
                    'login-bind' => ['post'],
                ],
            ],
        ];
    }


public function actionIndex()
    { 

//นับจำนวนข้อมูล
        //$DataProject= Researchpro::find()->all();
        
        $session = Yii::$app->session;
            $ty= 1;
            $model = Organize::findOne(1);       
        $session['ty']= $model->org_id;
        $session['ty_name']= $model->org_name;


       $searchModel = Researchpro::find()->all();

            $countProject = Researchpro::find()->count();
            $countuser = Account::find()->count();
            $countArticle = Article::find()->count();
            $countUtilization = Utilization::find()->count();


//ดึงข้อมูลกราฟ

            $Organize = Organize::find()->all();
            foreach ($Organize as $Org) {
                $categoriesOrganize[]=$Org->org_name;
                $seriescountuser[] = intval(Account::find()->where(['org_id'=>$Org->org_id])->count());
                $seriescountProject[] = intval(Researchpro::find()->where(['org_id'=>$Org->org_id])->count());
                $seriescountArticle[] = intval(Article::find()->where(['org_id'=>$Org->org_id])->count());
                $seriescountUtilization[] = intval(Utilization::find()->where(['org_id'=>$Org->org_id])->count());
            }
            $seriesOrganize=[
                ['name'=>'จำนวนนักวิจัย','data' => $seriescountuser],
                ['name'=>'จำนวนโครงการวิจัย','data' =>$seriescountProject],
                ['name'=>'จำนวนการตีพิมพ์เผยแพร่','data' =>$seriescountArticle],
                ['name'=>'จำนวนการใช้ประโยชน์','data' =>$seriescountUtilization],
            ];  

            $Publication = Publication::find()->all();

            $Resyear = Resyear::find()->orderBy('resyear DESC')->all();
            foreach ($Resyear as $Resyears) {
                $categoriesResyear[]=$Resyears->resyear;
                $ye = $Resyears->resyear-543;
                    $ResyearProject[] = intval(Researchpro::find()->where(['LIKE','projectYearsubmit',$Resyears->resyear])->andwhere(['org_id'=>$ty])->count());
                    $ResyearArticle[] = intval(Article::find()->where(['LIKE','article_publish',$ye])->andwhere(['org_id'=>$ty])->count());
                    $ResyearUtilization[] = intval(Utilization::find()->where(['LIKE','utilization_date',$ye])->andwhere(['org_id'=>$ty])->count());
            }

            $seriesResyear=[
                ['name'=>'โครงการวิจัย','data' =>$ResyearProject],
                ['name'=>'บทความวิชาการ','data' =>$ResyearArticle],
                ['name'=>'การใช้ประโยชน์','data' =>$ResyearUtilization],
            ];
            
            foreach ($Publication as $Publications) {
                foreach ($Resyear as $Resyears) {
                    $ye = $Resyears->resyear-543;

                        $ResyearPublication[] = intval(Article::find()->where(['LIKE','article_publish',$ye])->andwhere(['publication_type'=>$Publications->publication_type])->count());

                }
                
                $seriesPublication[]=['name'=>$Publications->publication_name,'data' =>$ResyearPublication];
                $ResyearPublication=array();
            };

            $ResGency = ResGency::find()->all();
            foreach ($ResGency as $ResGencys) {
                $sumall=0;
                $sumall1=0;
                $sumyear1=0;
                $sumyear2=0;
                $sumyear3=0;
                $sumyear10=0;
                $sumyear20=0;
                $sumyear30=0;
                $y1=date('Y')+543;
                $y2=$y1-1;
                $y3=$y2-1;
                $x=1;
                $categoriesResGency[]=$ResGencys->fundingAgencyName;
                if($ty==11){
                    $pieRes = intval(Researchpro::find()->where(['fundingAgencyID'=>$ResGencys->fundingAgencyID])->count());
                    $budgetRes = Researchpro::find()->where(['fundingAgencyID'=>$ResGencys->fundingAgencyID])->all();
                        foreach ($budgetRes as $budget) {
                            $sumall=intval($budget->budgets);
                            $sumall1=$sumall1+$sumall;
                        if($y1==$budget->projectYearsubmit){ 
                            $sumyear1=intval($budget->budgets);
                            $sumyear10=$sumyear10+$sumyear1;
                        }
                        if($y2==$budget->projectYearsubmit){ 
                            $sumyear2=intval($budget->budgets);
                            $sumyear20=$sumyear20+$sumyear2;
                        }
                        if($y3==$budget->projectYearsubmit){ 
                            $sumyear3=intval($budget->budgets);
                            $sumyear30=$sumyear30+$sumyear3;
                        }
                    }
                            
                }else{
                    $pieRes = intval(Researchpro::find()->where(['fundingAgencyID'=>$ResGencys->fundingAgencyID])->andwhere(['org_id'=>$ty])->count());
                    $budgetRes = Researchpro::find()->where(['fundingAgencyID'=>$ResGencys->fundingAgencyID])->andwhere(['org_id'=>$ty])->all();
                    foreach ($budgetRes as $budget) {
                        $sumall=intval($budget->budgets);
                        $sumall1=$sumall1+$sumall;
                        if($y1==$budget->projectYearsubmit){ 
                            $sumyear1=intval($budget->budgets);
                            $sumyear10=$sumyear10+$sumyear1;
                        }
                        if($y2==$budget->projectYearsubmit){ 
                            $sumyear2=intval($budget->budgets);
                            $sumyear20=$sumyear20+$sumyear2;
                        }
                        if($y3==$budget->projectYearsubmit){ 
                            $sumyear3=intval($budget->budgets);
                            $sumyear30=$sumyear30+$sumyear3;
                        }

                    }

                }

                $pieResGency[]=$pieRes;
                $piebudgetRes[]=$sumall1;
                $sumyear11[]=$sumyear10;
                $sumyear22[]=$sumyear20;
                $sumyear33[]=$sumyear30;

                $pieyearbudget=[['name'=>$y1, 'data'=>$sumyear11],['name'=>$y2, 'data'=>$sumyear22],['name'=>$y3, 'data'=>$sumyear33]];


            }




       return $this->render('index', [
            'model' => $model,
            'countProject'=>$countProject,
            'countuser'=> $countuser,
            'countArticle' =>  $countArticle,
            'countUtilization' => $countUtilization,
            'categoriesOrganize' => $categoriesOrganize,
            'seriesOrganize' => $seriesOrganize,
            'categoriesResyear' => $categoriesResyear,
            'seriesResyear' => $seriesResyear,
            'categoriesResGency' => $categoriesResGency,
            'pieResGency' => $pieResGency,
            'piebudgetRes' => $piebudgetRes,
            'pieyearbudget' => $pieyearbudget,
            'seriesPublication'=>$seriesPublication,

        ]);
    }
    /** Guard หน้า Login (แทน /site/sso) */
    public function actionLogin()
    {
        return $this->render('login');
    }

    /** รับ token จาก client -> ตรวจกับ HRM -> login Yii */
    public function actionLoginBind()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $jwt = Yii::$app->request->post('token');
        $pid = Yii::$app->request->post('personal_id');
        if (!$jwt || !$pid) return ['ok'=>false,'error'=>'missing token or personal_id'];

        try {
            $profile = $this->callHrm('profile', $jwt, ['personal_id' => $pid]);
        } catch (\Throwable $e) {
            return ['ok'=>false,'error'=>'authen/profile failed','detail'=>$e->getMessage()];
        }
        if (!$profile || empty(($profile['profile'] ?? $profile)['personal_id'])) {
            return ['ok'=>false,'error'=>'invalid profile'];
        }

        $identity = \app\models\User::fromToken($jwt, $profile);
        Yii::$app->user->login($identity, 3600*8);
        Yii::$app->session->set('identity', [
            'id'=>$identity->id,'username'=>$identity->username,'name'=>$identity->name,
            'email'=>$identity->email,'roles'=>$identity->roles,'profile'=>$identity->profile,
            'access_token'=>$identity->access_token,
        ]);

        return ['ok'=>true];
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        Yii::$app->session->remove('identity');
        return $this->redirect(['site/login']);   // ← เดิม site/sso
    }

    /** ==== HRM API helper ==== */
    private function callHrm(string $endpoint, string $jwt, array $payload): array
    {
        $base = Yii::$app->params['hrmApiBase']; // https://sci-sskru.com/authen
        $url  = rtrim($base,'/').'/'.$endpoint;

        if (class_exists(\yii\httpclient\Client::class)) {
            $client = new \yii\httpclient\Client(['transport'=>'yii\httpclient\CurlTransport']);
            $res = $client->createRequest()
                ->setMethod('POST')->setUrl($url)
                ->setHeaders(['Authorization'=>'Bearer '.$jwt,'Content-Type'=>'application/json'])
                ->setContent(json_encode($payload))->send();
            if (!$res->isOk) throw new \RuntimeException("HTTP {$res->statusCode}: ".$res->content);
            return is_array($res->data) ? $res->data : json_decode($res->content, true);
        }

        // fallback cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$jwt,'Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>20
        ]);
        $out = curl_exec($ch); $err = curl_error($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($out === false || $code < 200 || $code >= 300) throw new \RuntimeException("HTTP {$code}: ".($out ?: $err));
        $data = json_decode($out, true); return is_array($data) ? $data : [];
    }
    public function actionMyProfile()
    {
        // ดึงโปรไฟล์ของผู้ใช้ปัจจุบัน (ใช้ token + personal_id จาก JWT)
        $profile = \Yii::$app->apiAuth->getMyProfile();
        return $this->render('my-profile', ['profile' => $profile]);
    }

        /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        return $this->render('contact');
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

}
