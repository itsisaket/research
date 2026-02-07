<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

use app\models\WorkContributor;
use app\models\Article;    
use app\models\Researchpro;
//use app\models\AcademicService;
use app\models\Utilization;

class WorkContributorController extends Controller
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
                        'actions' => ['add', 'delete', 'update-pct'],
                        'allow'   => true,
                        'roles'   => [1, 4], // researcher/admin ตามระบบคุณ
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'add' => ['POST'],
                    'delete' => ['POST'],
                    'update-pct' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * เพิ่มผู้ร่วมหลายคน (Select2 multiple)
     * POST: WorkContributor[usernames][], role_code_form, pct_form, sort_order, note
     * GET : refType, refId, returnUrl
     */
    public function actionAdd($refType, $refId, $returnUrl = null)
    {
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        // policy: อนุญาตทุกคนที่ผ่าน roles เพิ่มได้ (ถ้าต้องการ owner-only ให้เรียก ensureOwner)
        // $this->ensureOwner($refType, $refId);

        $form = new WorkContributor();
        $form->scenario = 'multi';
        $form->ref_type = (string)$refType;
        $form->ref_id   = (int)$refId;

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {

            $role = $form->role_code_form ?: 'member';
            $startOrder = (int)$form->sort_order;

            $pct = $form->pct_form;
            $pct = ($pct === '' || $pct === null) ? null : (float)$pct;

            $selected = (array)$form->usernames;
            $selected = array_map('trim', $selected);
            $selected = array_values(array_unique(array_filter($selected, static function ($v) {
                return $v !== '';
            })));

            $tx = Yii::$app->db->beginTransaction();
            try {
                $created = 0;
                $i = 0;

                foreach ($selected as $uname) {
                    $row = new WorkContributor();
                    $row->ref_type = (string)$refType;
                    $row->ref_id   = (int)$refId;
                    $row->username = $uname;
                    $row->role_code = $role;
                    $row->sort_order = $startOrder + $i;
                    $row->note = $form->note;
                    $row->contribution_pct = $pct;

                    // กันซ้ำแบบนิ่ม ๆ (ชน UNIQUE ก็ข้าม)
                    try {
                        if ($row->save(false)) {
                            $created++;
                            $i++;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                $tx->commit();
                Yii::$app->session->setFlash('success', "เพิ่มผู้ร่วมสำเร็จ {$created} คน");
            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::$app->session->setFlash('error', 'บันทึกไม่สำเร็จ: ' . $e->getMessage());
            }
        }

        return $this->redirect($returnUrl ?: Yii::$app->request->referrer ?: ['/site/index']);
    }

    /**
     * ลบผู้ร่วม (owner-only)
     * POST: wc_id
     * GET : returnUrl
     */
    public function actionDelete($wc_id, $returnUrl = null)
    {
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        $row = WorkContributor::findOne((int)$wc_id);
        if (!$row) {
            throw new NotFoundHttpException('ไม่พบผู้ร่วม');
        }

        // owner-only
        $this->ensureOwner($row->ref_type, (int)$row->ref_id);

        $row->delete();
        Yii::$app->session->setFlash('success', 'ลบผู้ร่วมแล้ว');

        return $this->redirect($returnUrl ?: Yii::$app->request->referrer ?: ['/site/index']);
    }

    /**
     * ปรับสัดส่วน (%) รายคน (owner-only)
     * POST: pct
     * GET : wc_id, returnUrl
     */
    public function actionUpdatePct($wc_id, $returnUrl = null)
    {
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        $row = WorkContributor::findOne((int)$wc_id);
        if (!$row) {
            throw new NotFoundHttpException('ไม่พบผู้ร่วม');
        }

        // owner-only
        $this->ensureOwner($row->ref_type, (int)$row->ref_id);

        $pct = Yii::$app->request->post('pct');
        $pct = ($pct === '' || $pct === null) ? null : (float)$pct;

        if ($pct !== null && ($pct < 0 || $pct > 100)) {
            Yii::$app->session->setFlash('error', 'สัดส่วนต้องอยู่ระหว่าง 0–100');
            return $this->redirect($returnUrl ?: Yii::$app->request->referrer ?: ['/site/index']);
        }

        $row->contribution_pct = $pct;
        $row->save(false, ['contribution_pct']);

        Yii::$app->session->setFlash('success', 'อัปเดตสัดส่วนแล้ว');
        return $this->redirect($returnUrl ?: Yii::$app->request->referrer ?: ['/site/index']);
    }

    /**
     * ✅ ตรวจ owner ของ refType/refId (ตอนนี้รองรับ article แล้ว)
     * เพิ่มโมดูลอื่นได้ด้วยการเติม case.
     */
    protected function ensureOwner(string $refType, int $refId): void
    {
        $me = Yii::$app->user->identity ?? null;
        $myUsername = $me->username ?? null;

        if (!$myUsername) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        $ownerUsername = null;

        switch ($refType) {
            case 'article':
                $m = Article::findOne((int)$refId);
                $ownerUsername = $m->username ?? null;
                break;

            // ✅ เปิดใช้เมื่อคุณมีโมเดล/PK ชัดเจน
             case 'researchpro':
                 $m = Researchpro::findOne((int)$refId);
                 $ownerUsername = $m->username ?? null;
                 break;

            // case 'academic_service':
            //     $m = AcademicService::findOne((int)$refId);
            //     $ownerUsername = $m->username ?? null;
            //     break;

             case 'utilization':
                 $m = Utilization::findOne((int)$refId);
                 $ownerUsername = $m->username ?? null;
                 break;

            default:
                // ไม่รู้จัก refType → บล็อกไว้ก่อนเพื่อความปลอดภัย
                throw new ForbiddenHttpException('ไม่รองรับโมดูลนี้');
        }

        if (!$ownerUsername || (string)$ownerUsername !== (string)$myUsername) {
            throw new ForbiddenHttpException('แก้ไขได้เฉพาะเจ้าของเรื่อง');
        }
    }
}
