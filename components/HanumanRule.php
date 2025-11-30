<?php

namespace app\components;

use Yii;
use yii\filters\AccessRule;
use yii\web\ForbiddenHttpException;

class HanumanRule extends AccessRule
{
    protected function matchRole($user)
    {
        $actionId = Yii::$app->controller->action->id ?? null;

        // ปล่อย error เสมอ
        if ($actionId === 'error') {
            return true;
        }

        // ถ้า rule ไม่ได้กำหนด roles เลย → ถือว่า public
        if (empty($this->roles)) {
            return true;
        }

        // ---------- 1) กรณียังไม่ล็อกอิน (guest) ----------
        if ($user->getIsGuest()) {
            foreach ($this->roles as $role) {
                if ($role === '?') {
                    return true; // อนุญาต rule ที่ระบุ '?'
                }
            }
            return false; // rule นี้ต้องใช้ role อย่างอื่น
        }

        // ---------- 2) กรณีล็อกอินแล้ว ----------
        $identity = $user->identity;
        if (!$identity) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        // ผูก role กับ field position
        $u_type = (int)($identity->position ?? 0);

        $roleMap = [
            'researcher' => 1,
            'staff'      => 2,
            'executive'  => 3,
            'admin'      => 4,
        ];

        foreach ($this->roles as $role) {
            // '@' = ใครก็ได้ที่ล็อกอินแล้ว
            if ($role === '@') {
                return true;
            }

            // ระบุเป็นตัวเลข เช่น '1', '4'
            if (is_numeric($role) && (int)$role === $u_type) {
                return true;
            }

            // ระบุเป็นชื่อ เช่น 'admin', 'researcher'
            if (isset($roleMap[$role]) && $roleMap[$role] === $u_type) {
                return true;
            }
        }

        return false;
    }
}
