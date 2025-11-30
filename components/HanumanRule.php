<?php

namespace app\components;

use Yii;
use yii\filters\AccessRule;
use app\models\User; // 👈 identity class ที่คุณใช้จริง

class HanumanRule extends AccessRule
{
    /**
     * Match access by roles defined in AccessControl rule
     *
     * รูปแบบ roles ที่รองรับ:
     * - '?'          = guest เท่านั้น
     * - '@'          = ผู้ใช้ที่ล็อกอินแล้ว (และ token ยังไม่หมดอายุ)
     * - string       = 'researcher', 'admin', ... (เทียบกับ $identity->roles จาก JWT)
     * - int / "int"  = เลขรหัสสิทธิ เช่น 1, 4 (เทียบกับ roles ที่เป็นตัวเลข หรือ position ถ้าเป็นตัวเลข)
     */
    protected function matchRole($user)
    {
        // 1) ไม่กำหนด roles เลย → allow ทุกคน
        if (empty($this->roles)) {
            return true;
        }

        // 2) ถ้ายังเป็น guest
        if ($user->getIsGuest()) {
            // allow เฉพาะกรณีมี '?' ใน rule
            return in_array('?', $this->roles, true);
        }

        // 3) จากนี้ไปคือผู้ใช้ที่ล็อกอินแล้ว
        $identity = $user->identity;

        // ถ้า identity ไม่ใช่ User ของเรา แต่ rule ขอ '@' → ให้ผ่านในฐานะ authenticated เฉย ๆ
        if (!$identity instanceof User) {
            return in_array('@', $this->roles, true);
        }

        // 4) ถ้า token หมดอายุแล้ว → ไม่ให้ผ่าน
        if ($identity->isExpired()) {
            return false;
        }

        // 5) ถ้า rule มีแค่ '@' อย่างเดียว → คนไหนล็อกอินอยู่และไม่หมดอายุ ก็ผ่าน
        $hasExtraRole = false;
        foreach ($this->roles as $r) {
            if ($r !== '@' && $r !== '?') {
                $hasExtraRole = true;
                break;
            }
        }
        if (!$hasExtraRole && in_array('@', $this->roles, true)) {
            return true;
        }

        // 6) ดึง user roles จาก JWT (identity->roles) เป็น array ของ string
        $userRoles = [];
        if (is_array($identity->roles)) {
            foreach ($identity->roles as $r) {
                $userRoles[] = (string)$r;
            }
        }

        // 7) หารหัสสิทธิ (numeric) เผื่อใช้ map กับ constant
        $numericCode = null;

        // 7.1 หาเลขจาก roles ถ้า JWT ส่งมาแบบ ["1","4"]
        foreach ($userRoles as $r) {
            if (ctype_digit($r)) {
                $numericCode = (int)$r;
                break;
            }
        }

        // 7.2 ถ้ายังไม่มี และ position เป็นตัวเลข → ใช้ position แทน
        if ($numericCode === null && isset($identity->position) && is_numeric($identity->position)) {
            $numericCode = (int)$identity->position;
        }

        // 8) map ชื่อ role → รหัส (ตาม constant ใน User)
        $roleMap = [
            'researcher' => User::researcher,
            'staff'      => User::staff,
            'executive'  => User::executive,
            'admin'      => User::admin,
        ];

        // 9) ไล่เช็คตาม roles ที่กำหนดใน rule
        foreach ($this->roles as $role) {
            // ข้ามสัญลักษณ์พิเศษ (จัดการไปแล้วด้านบน)
            if ($role === '@' || $role === '?') {
                continue;
            }

            // 9.1 ถ้า rule เป็น string เช่น 'researcher', 'admin'
            if (is_string($role)) {
                // เทียบตรง ๆ กับ JWT roles
                if (in_array($role, $userRoles, true)) {
                    return true;
                }

                // ถ้า map เป็นเลขได้ และฝั่งผู้ใช้มี numericCode → เทียบเลข
                if (isset($roleMap[$role]) && $numericCode !== null) {
                    if ($numericCode === (int)$roleMap[$role]) {
                        return true;
                    }
                }

                // เผื่อมีเคสที่ position ส่งมาเป็นชื่อ string เช่น 'admin'
                if (!empty($identity->position) && (string)$identity->position === $role) {
                    return true;
                }

                continue;
            }

            // 9.2 ถ้า rule เขียนเป็นเลข เช่น 1, 4 → เทียบกับ numericCode (จาก roles หรือ position)
            if (is_int($role) || ctype_digit((string)$role)) {
                $roleInt = (int)$role;
                if ($numericCode !== null && $numericCode === $roleInt) {
                    return true;
                }
            }
        }

        // ไม่เข้าเงื่อนไขใด ๆ → ไม่ผ่าน
        return false;
    }
}
