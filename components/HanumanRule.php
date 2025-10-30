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
        return false; // ห้ามผู้ใช้ที่ยังไม่ได้ล็อกอิน
    }

    // ดึงข้อมูล user จาก session
    $identity = Yii::$app->user->identity;

    if (!$identity) {
        throw new ForbiddenHttpException('กรุณาเข้าสู่ระบบ');
    }

    // แปลงค่า u_status1 และ u_type เป็นตัวเลข
    $u_type = intval($identity->position);

    foreach ($this->roles as $role) {
        if ($role === '?') {
            if ($user->getIsGuest()) {
                return true;
            }
        } elseif ($role === '@') {
            if (!$user->getIsGuest()) {
                return true;
            }
        } elseif (in_array($u_type, array_map('intval', $this->roles))) {
            return true;
        }
    }

    return false;
}


}
