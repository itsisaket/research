<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use sizeg\jwt\Jwt;
use yii\web\Response;

use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class AuthController extends Controller
{
        /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    /**
     * แสดงฟอร์ม login และประมวลผลการ login ผ่าน LoginForm
     */
    public function actionLogin()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $token = Yii::$app->session->get('jwt_token');
            return $this->redirect(['auth/profile']);
        }

        // ✅ เพิ่มตรงนี้
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * สำหรับ API login: รับ POST แล้วส่ง JWT กลับเป็น JSON
     */
   public function actionLoginApi()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['status' => 'error', 'message' => 'Method not allowed'];
        }

        $uname = Yii::$app->request->post('uname');
        $pwd = Yii::$app->request->post('pwd');

        if (!$uname || !$pwd) {
            return ['status' => 'error', 'message' => 'Missing parameters'];
        }

        if ($uname === $pwd) {
            $jwt = Yii::$app->jwt;
            $token = $jwt->getBuilder()
                ->issuedBy('https://sci-sskru.com/authen/login')
                ->identifiedBy('4f1g23a12aa', true)
                ->issuedAt(time())
                ->expiresAt(time() + 3600)
                ->withClaim('uid', $uname)
                ->getToken($jwt->getSigner('HS256'), $jwt->getKey());

            return [
                'status' => 'success',
                'token' => (string) $token,
            ];
        }

        return ['status' => 'error', 'message' => 'Invalid credentials'];
    }



    /**
     * API: ตรวจสอบ token และแสดงข้อมูลผู้ใช้
     */
    public function actionProfile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return ['status' => 'error', 'message' => 'Authorization header missing or invalid'];
        }

        $tokenString = str_replace('Bearer ', '', $authHeader);

        try {
            $jwt = Yii::$app->jwt;
            $parsed = $jwt->getParser()->parse($tokenString);
            $validation = $jwt->getValidationData();
            $validation->setIssuer('https://sci-sskru.com/authen/login');

            if (!$parsed->validate($validation)) {
                throw new \Exception("Invalid or expired token");
            }

            return [
                'status' => 'success',
                'uid' => $parsed->getClaim('uid'),
                'data' => 'Your profile data here',
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function actionViewProfile()
    {
        // ดึง token จาก session แล้วเรียก API ภายนอก (HRM)
        $token = Yii::$app->session->get('jwt_token');

        $client = new \yii\httpclient\Client(['baseUrl' => 'https://sci-sskru.com/hrm-api/v1']);
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl('profile')
            ->addHeaders(['Authorization' => 'Bearer ' . $token])
            ->send();

        if ($response->isOk) {
            return $this->render('profile', ['profile' => $response->data]);
        }

        return $this->render('profile', ['profile' => null]);
    }

    public function actionLogout()
    {
        Yii::$app->session->remove('jwt_token');
        Yii::$app->user->logout(); // สำหรับระบบ login ของ Yii ถ้ามี
        return $this->goHome();
    }
}

