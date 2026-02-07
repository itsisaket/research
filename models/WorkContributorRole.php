<?php
namespace app\models;

use yii\db\ActiveRecord;

class WorkContributorRole extends ActiveRecord
{
    public static function tableName()
    {
        return 'work_contributor_role';
    }

    public static function items()
    {
        return static::find()
            ->select(['role_name'])
            ->indexBy('role_code')
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->column();
    }
}
