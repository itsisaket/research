<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_branch".
 *
 * @property int $branch_id รหัสสาขา
 * @property string $branch_name สาขาที่เกี่ยวข้อง
 */
class TbBranch extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_branch';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['branch_name'], 'required'],
            [['branch_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'branch_id' => 'รหัสสาขา',
            'branch_name' => 'สาขาที่เกี่ยวข้อง',
        ];
    }
}
