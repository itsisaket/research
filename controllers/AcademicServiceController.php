<?php

namespace app\controllers;

use Yii;
use app\models\AcademicService;
use app\models\AcademicServiceSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\components\ExcelExporter;

class AcademicServiceController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    // ทุกคนดูได้
                    [
                        'actions' => ['index',  'error'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    // Export Excel: ต้องล็อกอินก่อน
                    [
                        'actions' => ['export'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                    [
                        'actions' => ['suggest'],
                        'allow'   => true,
                        'roles'   => ['?', '@'],
                    ],
                    // ต้อง login และเป็น position 1 หรือ 4
                    [
                        'actions' => ['view','create', 'update', 'delete'],
                        'allow'   => true,
                        'roles'   => ['@'],
                        'matchCallback' => function () {
                            $me = Yii::$app->user->identity;
                            $pos = $me ? (int)($me->position ?? 0) : 0;
                            return in_array($pos, [1, 4], true);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel  = new AcademicServiceSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Export ข้อมูลบริการวิชาการ (ตาม filter ปัจจุบัน) เป็นไฟล์ Excel (.xlsx)
     * - SearchModel มีการจำกัดสิทธิ์ตาม position อยู่แล้ว
     */
    public function actionExport()
    {
        $searchModel  = new AcademicServiceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // ดึงผู้ร่วมดำเนินงานแบบ batch
        $dataProvider->pagination = false;
        $models   = $dataProvider->getModels();
        $refIds   = \yii\helpers\ArrayHelper::getColumn($models, 'service_id');
        $contribs = ExcelExporter::fetchContributorsMap('academic_service', $refIds);

        $columns = [
            ['header' => 'ลำดับ', 'value' => function ($m, $i) { return $i + 1; }, 'format' => 'number'],
            ['header' => 'รหัสรายการ', 'value' => 'service_id'],
            ['header' => 'เรื่อง', 'value' => 'title'],
            ['header' => 'ประเภทบริการวิชาการ', 'value' => function ($m) {
                return $m->serviceType->type_name ?? '';
            }],
            ['header' => 'ลักษณะงาน', 'value' => 'work_desc'],
            ['header' => 'สถานที่', 'value' => 'location'],
            ['header' => 'วันที่ปฏิบัติงาน', 'value' => function ($m) {
                return ExcelExporter::formatThaiDate($m->service_date);
            }],
            ['header' => 'จำนวนชั่วโมง', 'value' => 'hours', 'format' => 'number'],
            ['header' => 'เจ้าของรายการ', 'value' => function ($m) {
                return $m->getOwnerFullname() ?: ($m->username ?? '');
            }],
            ['header' => 'ผู้ร่วมดำเนินงาน', 'value' => function ($m) use ($contribs) {
                return $contribs[(int)$m->service_id] ?? '';
            }],
            ['header' => 'ลิงก์/อ้างอิง', 'value' => 'reference_url'],
            ['header' => 'หมายเหตุ', 'value' => 'note'],
            ['header' => 'สถานะ', 'value' => function ($m) {
                return ((int)$m->status === 1) ? 'ใช้งาน' : 'ปิดใช้';
            }],
        ];

        return ExcelExporter::export($dataProvider, $columns, [
            'filename'   => 'academic_service_' . date('Ymd_His'),
            'sheetTitle' => 'บริการวิชาการ',
            'title'      => 'รายการบริการวิชาการ',
            'subtitle'   => 'พิมพ์เมื่อ ' . ExcelExporter::formatThaiDate(date('Y-m-d')),
        ]);
    }

    /**
     * Autocomplete suggestions
     */
    public function actionSuggest($q = '')
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $q = trim((string)$q);
        if (mb_strlen($q) < 2) {
            return ['items' => []];
        }

        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $pos = $me ? (int)($me->position ?? 0) : 0;

        $query = AcademicService::find()->alias('s')
            ->leftJoin('tb_user u', 'u.username = s.username')
            ->select([
                's.service_id',
                's.title',
                's.location',
                's.service_date',
                'u.uname',
                'u.luname',
            ])
            ->andWhere(['or',
                ['like', 's.title',     $q],
                ['like', 's.location',  $q],
                ['like', 's.work_desc', $q],
                ['like', 'u.uname',     $q],
                ['like', 'u.luname',    $q],
            ])
            ->orderBy(['s.service_date' => SORT_DESC, 's.service_id' => SORT_DESC])
            ->limit(8)
            ->asArray();

        // จำกัดสิทธิ์เหมือน SearchModel
        if (Yii::$app->user->isGuest) {
            $query->andWhere(['s.status' => 1]);
        } elseif ($pos === 1) {
            $query->andWhere(['s.username' => (string)$me->username]);
        } elseif ($pos !== 4) {
            $ty = Yii::$app->session->get('ty');
            $orgId = $ty ?: ($me->org_id ?? null);
            if (!empty($orgId)) {
                $query->andWhere(['s.org_id' => (int)$orgId]);
            } else {
                $query->andWhere(['s.username' => (string)$me->username]);
            }
        }

        $rows = $query->all();
        $items = [];
        foreach ($rows as $r) {
            $name = trim(((string)($r['uname'] ?? '')) . ' ' . ((string)($r['luname'] ?? '')));
            $sub = $name;
            if (!empty($r['location'])) {
                $sub .= ($sub ? ' • ' : '') . $r['location'];
            }
            $items[] = [
                'id'       => (int)$r['service_id'],
                'title'    => (string)$r['title'],
                'subtitle' => $sub,
                'url'      => \yii\helpers\Url::to(['view', 'service_id' => $r['service_id']]),
            ];
        }

        return ['items' => $items];
    }

    public function actionView($service_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($service_id),
        ]);
    }

    public function actionCreate()
    {
        $model = new AcademicService();

        $me  = Yii::$app->user->identity;
        $pos = (int)($me->position ?? 0);
        $isAdmin = ($pos === 4);

        // default ค่าเบื้องต้น (ช่วยให้ฟอร์ม/validate ผ่านง่าย)
        if (empty($model->service_date)) {
            $model->service_date = date('Y-m-d');
        }

        if ($this->request->isPost) {
            $post = $this->request->post();
            $model->load($post);

            // ✅ กัน user ปลอมค่า: บังคับ username/org_id ตามสิทธิ์
            $this->applyOwnerAndOrg($model, $me, $isAdmin, $post);

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'บันทึกรายการเรียบร้อยแล้ว');
                return $this->redirect(['view', 'service_id' => $model->service_id]);
            }

            Yii::$app->session->setFlash('error', 'บันทึกไม่สำเร็จ กรุณาตรวจสอบข้อมูล');
        } else {
            // GET ครั้งแรก: set owner/org ให้เลย (โดยเฉพาะ username required)
            $this->applyOwnerAndOrg($model, $me, $isAdmin, []);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($service_id)
    {
        $model = $this->findModel($service_id);

        $me  = Yii::$app->user->identity;
        $pos = (int)($me->position ?? 0);
        $isAdmin = ($pos === 4);

        // ✅ สิทธิ์แก้ไข: admin ได้ทุกเคส / researcher ได้เฉพาะ owner
        if (!$isAdmin) {
            $isOwner = ($me && (string)$me->username === (string)$model->username);
            if (!$isOwner) {
                throw new ForbiddenHttpException('คุณไม่มีสิทธิ์แก้ไขรายการนี้');
            }
        }

        if ($this->request->isPost) {
            $post = $this->request->post();
            $model->load($post);

            // ✅ กันปลอมค่า owner/org ตอนแก้ไขด้วย
            $this->applyOwnerAndOrg($model, $me, $isAdmin, $post);

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'แก้ไขรายการเรียบร้อยแล้ว');
                return $this->redirect(['view', 'service_id' => $model->service_id]);
            }

            Yii::$app->session->setFlash('error', 'บันทึกไม่สำเร็จ กรุณาตรวจสอบข้อมูล');
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($service_id)
    {
        $model = $this->findModel($service_id);

        $me = Yii::$app->user->identity;
        $pos = (int)($me->position ?? 0);

        // ✅ admin(4) ลบได้ทุกเคส
        if ($pos === 4) {
            $model->delete();
            Yii::$app->session->setFlash('success', 'ลบรายการเรียบร้อยแล้ว');
            return $this->redirect(['index']);
        }

        // ✅ researcher(1) ลบได้เฉพาะ owner
        $isOwner = ($me && (string)$me->username === (string)$model->username);
        if (!$isOwner) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ลบรายการนี้');
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'ลบรายการเรียบร้อยแล้ว');
        return $this->redirect(['index']);
    }

    /**
     * บังคับ owner/org ให้ถูกต้องตามสิทธิ์ (กันปลอมค่า post)
     * - admin: อนุญาตให้เลือก username/org_id ได้ (ถ้ามีส่งมา)
     * - non-admin: ล็อก username = user login และ org_id = session ty หรือ identity->org_id
     */
    protected function applyOwnerAndOrg(AcademicService $model, $me, bool $isAdmin, array $post): void
    {
        // org จาก session
        $ty = Yii::$app->session->get('ty');

        if ($isAdmin) {
            // admin: ถ้าไม่ส่งมา ให้ fallback เป็นตัวเอง
            if (empty($model->username) && !empty($me->username)) {
                $model->username = (string)$me->username;
            }
            // org_id ถ้าไม่ส่งมา ให้ fallback จาก ty หรือ identity
            if (empty($model->org_id)) {
                if (!empty($ty)) {
                    $model->org_id = (int)$ty;
                } elseif (!empty($me->org_id)) {
                    $model->org_id = (int)$me->org_id;
                }
            }
            return;
        }

        // non-admin: ล็อกให้แน่นอน
        if (!empty($me->username)) {
            $model->username = (string)$me->username;
        }

        if (!empty($ty)) {
            $model->org_id = (int)$ty;
        } elseif (!empty($me->org_id)) {
            $model->org_id = (int)$me->org_id;
        }
    }

    protected function findModel($id)
    {
        if (($model = AcademicService::findOne(['service_id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('ไม่พบข้อมูลที่ร้องขอ');
    }
}
