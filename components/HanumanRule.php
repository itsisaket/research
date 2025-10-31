<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\components;

use Yii;
use yii\web\ForbiddenHttpException;
use app\models\User;

class HanumanRule extends \yii\filters\AccessRule
{
    /**
     * @inheritdoc
     */
    protected function matchRole($user)
    {
        // ✅ ปล่อยให้ทุกคนเข้าถึง action=error ได้
        $actionId = Yii::$app->controller->action->id ?? null;
        if ($actionId === 'error') {
            return true;
        }

        // ถ้า rule นี้ไม่กำหนด roles เลย → ผ่าน (เช่น index, regis แบบ public)
        if (empty($this->roles)) {
            return true;
        }

        // ถ้ายังไม่ได้ login และ rule นี้ต้องการ role → ไม่ผ่าน
        if ($user->getIsGuest()) {
            return false;
        }

        // ดึง identity
        $identity = Yii::$app->user->identity;
        if (!$identity) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        // ตรงนี้คุณผูก role กับตำแหน่งไว้ที่ field "position"
        $u_type = (int)$identity->position;

        // 🔹 กำหนด mapping ชื่อ role ↔ ตัวเลข
        $roleMap = [
            'researcher' => 1,
            'staff'      => 2,
            'executive'  => 3,
            'admin'      => 4,
        ];

        foreach ($this->roles as $role) {
            // guest
            if ($role === '?' && $user->getIsGuest()) {
                return true;
            }
            // logged in
            elseif ($role === '@' && !$user->getIsGuest()) {
                return true;
            }
            // ระบุเป็นตัวเลขตรง ๆ เช่น '4'
            elseif (is_numeric($role) && (int)$role === $u_type) {
                return true;
            }
            // ระบุเป็นชื่อ เช่น 'admin', 'researcher'
            elseif (isset($roleMap[$role]) && $roleMap[$role] === $u_type) {
                return true;
            }
        }

        return false;
    }
}
