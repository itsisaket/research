<?php

namespace app\controllers;

use Yii;
use app\models\Account;
use app\models\AccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;;

use app\models\Researchpro;
use app\models\Article;
use app\models\Utilization;
use app\models\AcademicService;
use app\models\WorkContributor;
use app\components\ExcelExporter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class AccountController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'ruleConfig' => [
                    'class' => \app\components\HanumanRule::class,
                ],
                'rules' => [
                    [
                        'actions' => ['index', 'error', 'view', 'suggest'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'actions' => ['resetpassword', 'export'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                    [
                        'actions' => ['create', 'update', 'delete'],
                        'allow'   => true,
                        'roles'   => ['@'],
                        'matchCallback' => function () {
                            $u = Yii::$app->user->identity;
                            return ($u instanceof \app\models\Account)
                                && in_array((int)$u->position, [1, 4], true);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-contributor' => ['POST'],
                    'update-contributor-pct' => ['POST'],
                ],
            ],
        ];
    }

    public function actionSyncProfile($id)
    {
        $model = Account::findOne($id);
        if (!$model) throw new NotFoundHttpException('ไม่พบผู้ใช้');

        if ($model->syncProfileFromAuthen()) {
            // save เฉพาะฟิลด์ที่ sync (กันกระทบ validation อื่น)
            $ok = $model->save(false, ['academic_type_name','first_name','last_name','dept_name','faculty_name']);
            Yii::$app->session->setFlash($ok ? 'success' : 'error', $ok ? 'Sync สำเร็จ' : 'Sync แล้วแต่บันทึกไม่สำเร็จ');
        } else {
            Yii::$app->session->setFlash('warning', 'ไม่พบข้อมูลจาก API หรือ personal_id ว่าง');
        }

        return $this->redirect(['index']);
    }

    public function actionIndex()
    {
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $searchModel = new AccountSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (!empty($ty)) {
            $dataProvider->query->andWhere(['a.org_id' => $ty]);
        }

        // ✅ username = personal_id
        $models = $dataProvider->getModels();
        $ids = [];
        foreach ($models as $m) {
            if (!empty($m->username)) $ids[] = (string)$m->username;
        }

        $profileMap = Yii::$app->sciProfile->getMap($ids);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'profileMap'   => $profileMap,
        ]);
    }


    /**
     * Export รายชื่อนักวิจัย (ตาม filter ปัจจุบัน) เป็นไฟล์ Excel
     * รวมจำนวนผลงานทั้ง 4 ประเภท (เจ้าของ + ผู้ร่วม) แบบ batch
     */
    public function actionExport()
    {
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $searchModel  = new AccountSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (!empty($ty)) {
            $dataProvider->query->andWhere(['a.org_id' => $ty]);
        }

        // ดึง model ทั้งหมด (ปิด pagination เพื่อให้ได้ครบ)
        $dataProvider->pagination = false;
        $models = $dataProvider->getModels();

        // เก็บ usernames ไว้ใช้ batch query
        $usernames = [];
        foreach ($models as $m) {
            if (!empty($m->username)) $usernames[] = (string)$m->username;
        }

        // นับผลงาน 4 ประเภทแบบ batch (เจ้าของ + ผู้ร่วม รวมกันโดยกันซ้ำ)
        $countMap = $this->buildWorkCountMap($usernames);

        // ดึง profile จาก SSO เพื่อแสดงชื่อสังกัด/ตำแหน่งวิชาการ
        $profileMap = !empty($usernames)
            ? Yii::$app->sciProfile->getMap($usernames)
            : [];

        $columns = [
            ['header' => 'ลำดับ', 'value' => function ($m, $i) { return $i + 1; }, 'format' => 'number'],
            ['header' => 'รหัสบุคลากร', 'value' => 'username'],
            ['header' => 'ชื่อ - สกุล', 'value' => function ($m) use ($profileMap) {
                $p = $profileMap[$m->username] ?? null;
                if (is_array($p)) {
                    $academic = trim((string)($p['academic_type_name'] ?? ''));
                    $name     = trim(($p['first_name'] ?? '').' '.($p['last_name'] ?? ''));
                    if ($name !== '') return trim($academic.' '.$name);
                }
                return trim(($m->uname ?? '').' '.($m->luname ?? ''));
            }],
            ['header' => 'หน่วยงาน', 'value' => function ($m) use ($profileMap) {
                $p = $profileMap[$m->username] ?? null;
                if (is_array($p) && !empty($p['faculty_name'])) {
                    return $p['faculty_name'];
                }
                return $m->hasorg->org_name ?? '';
            }],
            ['header' => 'สาขา/ภาควิชา', 'value' => function ($m) use ($profileMap) {
                $p = $profileMap[$m->username] ?? null;
                return is_array($p) ? (string)($p['dept_name'] ?? '') : '';
            }],
            ['header' => 'ตำแหน่ง', 'value' => function ($m) {
                return $m->hasposition->positionname ?? '';
            }],
            ['header' => 'อีเมล', 'value' => 'email'],
            ['header' => 'โทรศัพท์', 'value' => 'tel'],
            ['header' => 'งานวิจัย', 'value' => function ($m) use ($countMap) {
                return (int)($countMap['researchpro'][$m->username] ?? 0);
            }, 'format' => 'number'],
            ['header' => 'การตีพิมพ์', 'value' => function ($m) use ($countMap) {
                return (int)($countMap['article'][$m->username] ?? 0);
            }, 'format' => 'number'],
            ['header' => 'การนำไปใช้', 'value' => function ($m) use ($countMap) {
                return (int)($countMap['utilization'][$m->username] ?? 0);
            }, 'format' => 'number'],
            ['header' => 'บริการวิชาการ', 'value' => function ($m) use ($countMap) {
                return (int)($countMap['academic_service'][$m->username] ?? 0);
            }, 'format' => 'number'],
            ['header' => 'รวมผลงาน', 'value' => function ($m) use ($countMap) {
                $u = $m->username;
                return (int)($countMap['researchpro'][$u] ?? 0)
                     + (int)($countMap['article'][$u] ?? 0)
                     + (int)($countMap['utilization'][$u] ?? 0)
                     + (int)($countMap['academic_service'][$u] ?? 0);
            }, 'format' => 'number'],
        ];

        return ExcelExporter::export($dataProvider, $columns, [
            'filename'   => 'account_' . date('Ymd_His'),
            'sheetTitle' => 'นักวิจัย',
            'title'      => 'รายชื่อนักวิจัยและผลงาน',
            'subtitle'   => 'พิมพ์เมื่อ ' . ExcelExporter::formatThaiDate(date('Y-m-d')),
        ]);
    }

    /**
     * นับจำนวนผลงาน 4 ประเภท ของ username หลายคนแบบ batch
     * รวม "เจ้าของ" + "ผู้ร่วม" โดยกันซ้ำ
     *
     * @param string[] $usernames
     * @return array  ['researchpro' => [u1=>n,...], 'article'=>[...], 'utilization'=>[...], 'academic_service'=>[...]]
     */
    protected function buildWorkCountMap(array $usernames): array
    {
        $usernames = array_values(array_unique(array_filter($usernames)));
        $result = [
            'researchpro' => [],
            'article' => [],
            'utilization' => [],
            'academic_service' => [],
        ];
        if (empty($usernames)) {
            return $result;
        }

        // โครง: (table, refType, pk)
        $defs = [
            'researchpro'      => ['tb_researchpro',   'projectID'],
            'article'          => ['tb_article',       'article_id'],
            'utilization'      => ['tb_utilization',   'utilization_id'],
            'academic_service' => ['academic_service', 'service_id'],
        ];

        foreach ($defs as $refType => $info) {
            [$table, $pk] = $info;

            // 1) ดึง (username, ref_id) ของ "เจ้าของ" จากตารางหลัก
            $ownerRows = (new \yii\db\Query())
                ->select(["username", "$pk AS ref_id"])
                ->from($table)
                ->where(['username' => $usernames])
                ->all();

            // 2) ดึง (username, ref_id) ของ "ผู้ร่วม" จาก work_contributor
            $contribRows = (new \yii\db\Query())
                ->select(['username', 'ref_id'])
                ->from('work_contributor')
                ->where(['ref_type' => $refType, 'username' => $usernames])
                ->all();

            // 3) รวม 2 ชุด แล้วกันซ้ำในระดับ (username, ref_id) ก่อนนับ
            //    (กันกรณีคนเดียวกันเป็นทั้งเจ้าของและผู้ร่วมในรายการเดียวกัน)
            $bag = [];
            foreach (array_merge($ownerRows, $contribRows) as $r) {
                $u = (string)($r['username'] ?? '');
                $rid = (string)($r['ref_id'] ?? '');
                if ($u === '' || $rid === '') continue;
                $key = $u . '|' . $rid;
                if (!isset($bag[$key])) {
                    $bag[$key] = true;
                    $result[$refType][$u] = ($result[$refType][$u] ?? 0) + 1;
                }
            }
        }

        return $result;
    }

    /**
     * Autocomplete suggestions สำหรับ quick search รายชื่อนักวิจัย
     * GET ?q=keyword → JSON [{id,title,subtitle,url}, ...]
     */
    public function actionSuggest($q = '')
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $q = trim((string)$q);
        if (mb_strlen($q) < 2) {
            return ['items' => []];
        }

        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $query = Account::find()->alias('a')
            ->select(['a.uid', 'a.username', 'a.uname', 'a.luname', 'a.email', 'a.org_id'])
            ->andWhere(['or',
                ['like', 'a.username', $q],
                ['like', 'a.uname',    $q],
                ['like', 'a.luname',   $q],
                ['like', 'a.email',    $q],
            ])
            ->orderBy(['a.uname' => SORT_ASC, 'a.luname' => SORT_ASC])
            ->limit(8)
            ->asArray();

        if (!empty($ty)) {
            $query->andWhere(['a.org_id' => (int)$ty]);
        }

        $rows = $query->all();
        if (empty($rows)) {
            return ['items' => []];
        }

        // ดึง profile (ชื่อตำแหน่งวิชาการ + คณะ) แบบ batch
        $usernames = ArrayHelper::getColumn($rows, 'username');
        $profileMap = [];
        try {
            $profileMap = (array)Yii::$app->sciProfile->getMap($usernames);
        } catch (\Throwable $e) {
            // เผื่อ SSO/profile service ใช้ไม่ได้ — ยังคืน suggestion ได้
        }

        $items = [];
        foreach ($rows as $r) {
            $p = $profileMap[$r['username']] ?? null;

            $title = '';
            if (is_array($p) && !empty($p['first_name'])) {
                $academic = trim((string)($p['academic_type_name'] ?? ''));
                $name     = trim(($p['first_name'] ?? '').' '.($p['last_name'] ?? ''));
                $title    = trim($academic.' '.$name);
            }
            if ($title === '') {
                $title = trim(((string)($r['uname'] ?? '')) . ' ' . ((string)($r['luname'] ?? '')));
            }
            if ($title === '') $title = (string)$r['username'];

            $sub = '';
            if (is_array($p)) {
                $fac = trim((string)($p['faculty_name'] ?? ''));
                $dep = trim((string)($p['dept_name'] ?? ''));
                $sub = trim($fac . ($dep ? ' • ' . $dep : ''));
            }
            if ($sub === '' && !empty($r['email'])) $sub = (string)$r['email'];

            $items[] = [
                'id'       => (int)$r['uid'],
                'title'    => $title,
                'subtitle' => $sub,
                'url'      => Url::to(['view', 'id' => $r['uid']]),
            ];
        }

        return ['items' => $items];
    }

    /**
     * 1) รายชื่อเรื่องของผู้ใช้ (4 ตาราง)
     * 2) นับจำนวนเรื่อง (4 ตาราง)
     * 3) ข้อมูลผู้ใช้ (model)
     * ✅ ค้นจาก username เท่านั้น
     */
public function actionView($id)
{
    $model = $this->findModel($id);
    $username = (string)$model->username;

    /* =========================================================
     * 1) Latest (เจ้าของ) — เหมือนเดิม
     * ========================================================= */

    // งานวิจัย (เจ้าของล่าสุด)
    $researchLatest = Researchpro::find()
        ->where(['username' => $username])
        ->orderBy([Researchpro::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // บทความ (เจ้าของล่าสุด)
    $articleLatest = Article::find()
        ->where(['username' => $username])
        ->orderBy([Article::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // การนำไปใช้ (เจ้าของล่าสุด)
    $utilLatest = Utilization::find()
        ->where(['username' => $username])
        ->orderBy([Utilization::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    // บริการวิชาการ (เจ้าของล่าสุด)
    $serviceLatest = AcademicService::find()
        ->where(['username' => $username])
        ->orderBy([AcademicService::primaryKey()[0] => SORT_DESC])
        ->limit(10)
        ->all();

    /* =========================================================
     * 2) KPI รวม (เจ้าของ + ผู้ร่วม) แบบกันซ้ำ
     * ========================================================= */

    // --- งานวิจัย ---
    $researchPk = Researchpro::primaryKey()[0];

    $ownResearchIds = Researchpro::find()
        ->select($researchPk)
        ->where(['username' => $username]);

    $contribResearchIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username'  => $username,
            'ref_type'  => 'researchpro',
        ]);

    $cntResearch = (int)Researchpro::find()
        ->where(['or',
            ['in', $researchPk, $ownResearchIds],
            ['in', $researchPk, $contribResearchIds],
        ])
        ->distinct()
        ->count();


    // --- บทความ ---
    $articlePk = Article::primaryKey()[0];

    $ownArticleIds = Article::find()
        ->select($articlePk)
        ->where(['username' => $username]);

    $contribArticleIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username' => $username,
            'ref_type' => 'article',
        ]);

    $cntArticle = (int)Article::find()
        ->where(['or',
            ['in', $articlePk, $ownArticleIds],
            ['in', $articlePk, $contribArticleIds],
        ])
        ->distinct()
        ->count();


    // --- การนำไปใช้ ---
    $utilPk = Utilization::primaryKey()[0];

    $ownUtilIds = Utilization::find()
        ->select($utilPk)
        ->where(['username' => $username]);

    $contribUtilIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username' => $username,
            'ref_type' => 'utilization',
        ]);

    $cntUtil = (int)Utilization::find()
        ->where(['or',
            ['in', $utilPk, $ownUtilIds],
            ['in', $utilPk, $contribUtilIds],
        ])
        ->distinct()
        ->count();


    // --- บริการวิชาการ ---
    $servicePk = AcademicService::primaryKey()[0];

    $ownServiceIds = AcademicService::find()
        ->select($servicePk)
        ->where(['username' => $username]);

    $contribServiceIds = WorkContributor::find()
        ->select('ref_id')
        ->where([
            'username' => $username,
            'ref_type' => 'academic_service', // ✅ ต้องให้ตรงกับ ref_type ที่คุณใช้จริง
        ]);

    $cntService = (int)AcademicService::find()
        ->where(['or',
            ['in', $servicePk, $ownServiceIds],
            ['in', $servicePk, $contribServiceIds],
        ])
        ->distinct()
        ->count();


    /* =========================================================
     * 3) ดึงรายการผู้ร่วม (ไว้แสดงใน view) — เหมือนเดิม
     * ========================================================= */

    $contributors = WorkContributor::find()
        ->where(['username' => $username])
        ->orderBy(['ref_type' => SORT_ASC, 'sort_order' => SORT_ASC])
        ->all();

    $contribResearch = [];
    $contribArticle  = [];
    $contribUtil     = [];
    $contribService  = [];

    foreach ($contributors as $wc) {
        switch ($wc->ref_type) {

            case 'researchpro':
                if ($m = Researchpro::findOne((int)$wc->ref_id)) {
                    $contribResearch[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'article':
                if ($m = Article::findOne((int)$wc->ref_id)) {
                    $contribArticle[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'utilization':
                if ($m = Utilization::findOne((int)$wc->ref_id)) {
                    $contribUtil[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;

            case 'academic_service':
                if ($m = AcademicService::findOne((int)$wc->ref_id)) {
                    $contribService[] = [
                        'model' => $m,
                        'role'  => $wc->role_code,
                        'pct'   => $wc->contribution_pct,
                    ];
                }
                break;
        }
    }

    return $this->render('view', compact(
        'model',
        'cntResearch','cntArticle','cntUtil','cntService',
        'researchLatest','articleLatest','utilLatest','serviceLatest',
        'contribResearch','contribArticle','contribUtil','contribService'
    ));
}


protected function findModel($id)
{
    if (($model = Account::findOne(['uid' => (int)$id])) !== null) {
        return $model;
    }
    throw new NotFoundHttpException('ไม่พบข้อมูลผู้ใช้ที่ต้องการ');
}
}
