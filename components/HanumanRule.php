<?php

namespace app\components;

use Yii;
use yii\filters\AccessRule;
use app\models\Account; // ✅ ใช้ Account เป็น identity (แก้ชื่อให้ตรงโปรเจ็กต์คุณ)

class HanumanRule extends AccessRule
{
    /**
     * ตรวจสอบสิทธิ์ตาม roles ที่กำหนดในกฎของ AccessControl
     *
     * รองรับรูปแบบ roles:
     * - '?'          = guest เท่านั้น
     * - '@'          = ผู้ใช้ล็อกอินแล้ว (และอาจเช็คสถานะ active เพิ่มได้)
     * - ตัวเลข       = เทียบกับ $identity->position (int)
     * - ชื่อ string  = 'researcher', 'admin' → map เป็นเลขตำแหน่ง
     */
    protected function matchRole($user)
    {
        // 1) ถ้าไม่กำหนด roles ในกฎนี้เลย → อนุญาตทุกคน
        if (empty($this->roles)) {
            return true;
        }

        // 2) roles มี '?' → ผ่านเฉพาะ guest
        if (in_array('?', $this->roles, true)) {
            return $user->getIsGuest();
        }

        // 3) จากนี้ไปต้องเป็นผู้ใช้ที่ล็อกอินแล้ว
        if ($user->getIsGuest()) {
            return false;
        }

        /** @var Account|null $identity */
        $identity = $user->identity;

        // กันเคส identity ไม่ตรงชนิดที่คาดไว้
        if (!$identity instanceof Account) {
            // ถ้า roles มี '@' ก็ถือว่าแค่ล็อกอินแล้วพอ
            if (in_array('@', $this->roles, true)) {
                return true;
            }
            return false;
        }

        // 4) roles มี '@' → ผู้ใช้ที่ล็อกอินแล้วทุกคนผ่านได้
        //    ถ้าคุณอยากเช็คเฉพาะ active ก็เพิ่มเงื่อนไขตรงนี้ได้
        if (in_array('@', $this->roles, true)) {
            // ตัวอย่างถ้าคุณมีคอลัมน์สถานะ เช่น u_status2
            // return (int)$identity->u_status2 === 1;
            return true;
        }

        // 5) ดึงตำแหน่ง (position) ของผู้ใช้
        //    สมมติ: 1 = researcher, 4 = admin (ตามที่คุณคอมเมนต์ไว้)
        $position = isset($identity->position) ? (int)$identity->position : null;
        if ($position === null) {
            return false;
        }

        // 6) map ชื่อบทบาท (string) -> รหัสตำแหน่ง (int)
        $roleMap = [
            'researcher' => 1,
            'admin'      => 4,
            // เพิ่มได้ เช่น 'staff' => 2, 'manager' => 3, ...
        ];

        // 7) ไล่เช็ค roles ที่กำหนดในกฎทีละตัว
        foreach ($this->roles as $role) {

            // ข้ามสัญลักษณ์พิเศษ (จัดการไปแล้วด้านบน)
            if ($role === '@' || $role === '?') {
                continue;
            }

            // 7.1 ถ้าเป็นตัวเลขหรือ string ของตัวเลข → เทียบกับ position ตรง ๆ
            if (is_int($role) || ctype_digit((string)$role)) {
                if ($position === (int)$role) {
                    return true;
                }
                continue;
            }

            // 7.2 ถ้าเป็นชื่อ string เช่น 'researcher', 'admin'
            if (is_string($role) && isset($roleMap[$role])) {
                if ($position === $roleMap[$role]) {
                    return true;
                }
                continue;
            }

            // 7.3 เผื่อกรณีคุณมี field role เป็น string ใน Account เช่น 'admin', 'researcher'
            if (is_string($role) && property_exists($identity, 'role')) {
                if ((string)$identity->role === $role) {
                    return true;
                }
            }
        }

        // ไม่ตรงอะไรเลย → ไม่ผ่าน
        return false;
    }
}

