<?php

namespace app\components;

use Yii;
use yii\filters\AccessRule;
use yii\web\ForbiddenHttpException;

class HanumanRule extends AccessRule
{
    /**
     * ตรวจสอบสิทธิ์จาก roles ที่กำหนดใน rule
     */
    protected function matchRole($user)
    {
        $actionId = Yii::$app->controller->action->id ?? null;

        // ✅ ปล่อย action error เสมอ
        if ($actionId === 'error') {
            return true;
        }

        // ✅ ถ้า rule นี้ไม่กำหนด roles เลย → public
        if (empty($this->roles)) {
            return true;
        }

        // ==============================
        // 1) กรณี Guest (ยังไม่ล็อกอิน)
        // ==============================
        if ($user->getIsGuest()) {
            foreach ($this->roles as $role) {
                // อนุญาต rule ที่ระบุ '?'
                if ($role === '?') {
                    return true;
                }
            }
            // rule นี้ต้องการ role อื่น (เช่น '@' หรือชื่อ role) แต่เป็น guest → ไม่ผ่าน
            return false;
        }

        // ==============================
        // 2) กรณีล็อกอินแล้ว
        // ==============================
        $identity = $user->identity;
        if (!$identity) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        // 🔹 role ผูกกับ field position (ปรับชื่อตรงนี้ให้ตรงกับ model ของคุณ)
        $u_type = (int)($identity->position ?? 0);

        // 🔹 mapping ชื่อ role → ตัวเลข
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

            // ตัวเลขตรง ๆ เช่น '1', '4'
            if (is_numeric($role) && (int)$role === $u_type) {
                return true;
            }

            // ใช้ชื่อ role ตาม roleMap เช่น 'admin', 'researcher'
            if (isset($roleMap[$role]) && $roleMap[$role] === $u_type) {
                return true;
            }
        }

        return false;
    }
}
