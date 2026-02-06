<?php

namespace app\components;

use Yii;
use yii\filters\AccessRule;
use app\models\Account; // ใช้ Account เป็น identity

class HanumanRule extends AccessRule
{
    /**
     * รองรับ roles ได้ 4 แบบ:
     * 1) '?' guest
     * 2) '@' login
     * 3) ชื่อ role เช่น 'admin','researcher'
     * 4) ตัวเลข position เช่น 1,4 หรือ '1','4'
     */
    protected function matchRole($user)
    {
        // 1) ถ้าไม่กำหนด roles → ผ่านทุกคน
        if (empty($this->roles)) {
            return true;
        }

        // normalize roles: trim string และคงค่าเดิมของ non-string
        $roles = array_map(function ($r) {
            return is_string($r) ? trim($r) : $r;
        }, $this->roles);

        // 2) guest
        if ($user->getIsGuest()) {
            return in_array('?', $roles, true);
        }

        // 3) identity ต้องเป็น Account
        $identity = $user->identity;
        if (!$identity instanceof Account) {
            // ถ้า rule มี '@' อนุญาตคนที่ล็อกอินแล้ว
            return in_array('@', $roles, true);
        }

        // 4) ถ้า rule มี '@' อย่างเดียว → allow
        $hasExtra = false;
        foreach ($roles as $r) {
            if ($r !== '@' && $r !== '?') {
                $hasExtra = true;
                break;
            }
        }
        if (!$hasExtra && in_array('@', $roles, true)) {
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

        foreach ($roles as $role) {

            // ข้าม '@' และ '?'
            if ($role === '@' || $role === '?') {
                continue;
            }

            // (ก) role เป็น string เช่น 'admin'
            if (is_string($role)) {
                // string ชื่อ role
                if (isset($roleMap[$role]) && $position === (int)$roleMap[$role]) {
                    return true;
                }

                // string ตัวเลข เช่น "4"
                if (is_numeric($role) && $position === (int)$role) {
                    return true;
                }

                continue;
            }

            // (ข) role เป็นตัวเลข เช่น 1,4 หรือ float ที่เป็นตัวเลข
            if (is_numeric($role) && $position === (int)$role) {
                return true;
            }
        }

        return false;
    }
}
