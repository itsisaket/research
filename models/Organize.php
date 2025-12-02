<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "tb_organize".
 *
 * @property int    $org_id
 * @property string $org_name
 * @property string $org_address
 * @property string $org_tel
 */
class Organize extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_organize';
    }

    /**
     * ระบุ primary key (เพราะชื่อไม่ใช่ id)
     */
    public static function primaryKey()
    {
        return ['org_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['org_id', 'org_name'], 'required'],
            [['org_id'], 'integer'],
            [['org_address'], 'string'],
            [['org_name'], 'string', 'max' => 100],
            [['org_tel'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'org_id'      => 'รหัสหน่วยงาน/คณะ',
            'org_name'    => 'ชื่อหน่วยงาน/คณะ',
            'org_address' => 'ที่อยู่',
            'org_tel'     => 'โทรศัพท์',
        ];
    }
}
