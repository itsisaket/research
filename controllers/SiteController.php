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
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    ['allow' => true, 'roles' => ['@']], // ต้องล็อกอินก่อน
                ],
            ],
        ];
    }
/*
    public function actionIndex()
    {
        return $this->render('index');
    }
*/
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
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        // ลบคุกกี้ SSO ทั้งแบบไม่มีโดเมนและแบบโดเมนร่วม (กันพลาด)
        Yii::$app->response->cookies->remove('hrm-sci-token');
        Yii::$app->response->cookies->remove(new \yii\web\Cookie([
            'name' => 'hrm-sci-token',
            'domain' => '.sci-sskru.com',
            'path' => '/',
        ]));

        Yii::$app->session->remove('identity');
        Yii::$app->user->logout(true);
        Yii::$app->session->regenerateID(true);
        return $this->goHome();
    }

    public function actionPingAuth($u = '3331000521623', $p = '3331000521623')
    {
        $res = Yii::$app->apiClient->createRequest()
            ->setMethod('POST')
            ->setUrl('/authen/login')
            ->setFormat(\yii\httpclient\Client::FORMAT_JSON)
            ->setData(['uname' => (string)$u, 'pwd' => (string)$p])
            ->send();

        $data = $res->getData();
        $claims = [];
        if (is_array($data ?? null) && !empty($data['token'])) {
            $parts = explode('.', $data['token']);
            if (count($parts) >= 2) {
                $payload = $parts[1] . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);
                $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            }
        }

        return $this->asJson([
            'http_ok' => $res->isOk,
            'http_status' => $res->statusCode,
            'has_token' => !empty($data['token']),
            'claims' => $claims,
        ]);
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
