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

class HanumanRule extends \yii\filters\AccessRule{

    /**
     * @inheritdoc
     */
    /*
    protected function matchRole($user)
    {
        if(empty($this->roles)){
            return true;
        }
        foreach($this->roles as $role){
            if($role === '?'){
                if($user->getIsGuest()){
                    return true;
                }
            }else if($role === '@'){
                if(!$user->getIsGuest()){
                    return true;
                }
            }else if(!$user->getIsGuest() && $role === $user->identity->u_type){
           // }else if(!$user->getIsGuest() && $role === $user->identity->permission_id){
                return true;               
            }
        }
        return false;
    }
    */
    protected function matchRole($user)
    {
        if (empty($this->roles)) {
            return true;
        }

        if ($user->getIsGuest()) {
            return false;
        }

        $identity = Yii::$app->user->identity;
        if (!$identity) {
            throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
        }

        $u_type = intval($identity->position);

        // 🔹 กำหนด mapping ชื่อ role ↔ ตัวเลข
        $roleMap = [
            'researcher' => 1,
            'staff'      => 2,
            'executive'  => 3,
            'admin'      => 4,
        ];

        foreach ($this->roles as $role) {
            if ($role === '?' && $user->getIsGuest()) {
                return true;
            } elseif ($role === '@' && !$user->getIsGuest()) {
                return true;
            } elseif (is_numeric($role) && intval($role) === $u_type) {
                return true;
            } elseif (isset($roleMap[$role]) && $roleMap[$role] === $u_type) {
                return true;
            }
        }

        return false;
    }
}
