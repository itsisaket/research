<?php

namespace app\components;

use Yii;
use yii\filters\AccessRule;
use app\models\Account; // ใช้ Account เป็น identity

class HanumanRule extends AccessRule
{
    protected function matchRole($user)
    {
        // 1) ถ้าไม่กำหนด roles → ผ่านทุกคน
        if (empty($this->roles)) {
            return true;
        }

        // 2) guest
        if ($user->getIsGuest()) {
            return in_array('?', $this->roles, true);
        }

        // 3) identity ต้องเป็น Account
        $identity = $user->identity;
        if (!$identity instanceof Account) {
            // ถ้า rule มี '@' อนุญาตคนที่ล็อกอินแล้ว
            return in_array('@', $this->roles, true);
        }

        // 4) ถ้า rule มี '@' อย่างเดียว → allow
        $hasExtra = false;
        foreach ($this->roles as $r) {
            if ($r !== '@' && $r !== '?') {
                $hasExtra = true;
                break;
            }
        }
        if (!$hasExtra && in_array('@', $this->roles, true)) {
            return true;
        }

        // 5) ใช้ตำแหน่ง (position) จาก Account
        $position = (int)($identity->position ?? 0);

        // Map ชื่อ role → position
        $roleMap = [
            'researcher' => 1,
            'staff'      => 2,
            'executive'  => 3,
            'admin'      => 4,
        ];

        foreach ($this->roles as $role) {

            // ข้าม '@' และ '?'
            if ($role === '@' || $role === '?') {
                continue;
            }

            // (ก) rule เป็น string เช่น 'admin'
            if (is_string($role)) {
                if (isset($roleMap[$role]) && $position === (int)$roleMap[$role]) {
                    return true;
                }
                continue;
            }

            // (ข) rule เป็นตัวเลข เช่น 1, 4
            if (is_int($role) || ctype_digit((string)$role)) {
                if ($position === (int)$role) {
                    return true;
                }
            }
        }

        return false;
    }
}
