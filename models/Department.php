<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "tb_department".
 *
 * @property int $dept_id
 * @property string $dept_name
 * @property int $org_id
 * @property string|null $dept_tel
 * @property string|null $dept_address
 *
 * @property Organize $organize
 */
class Department extends ActiveRecord
{
    public static function tableName()
    {
        return 'tb_department';
    }

    public static function primaryKey()
    {
        return ['dept_id'];
    }

    public function rules()
    {
        return [
            [['dept_id', 'dept_name', 'org_id'], 'required'],
            [['dept_id', 'org_id'], 'integer'],
            [['dept_address'], 'string'],
            [['dept_name'], 'string', 'max' => 150],
            [['dept_tel'], 'string', 'max' => 20],
        ];
    }

    public function attributeLabels()
    {
        return [
            'dept_id'      => 'รหัสภาควิชา',
            'dept_name'    => 'ชื่อภาควิชา',
            'org_id'       => 'รหัสคณะ',
            'dept_tel'     => 'เบอร์โทร',
            'dept_address' => 'ที่อยู่',
        ];
    }

    /**
     * ความสัมพันธ์ไปยังคณะ / Organize
     */
    public function getOrganize()
    {
        return $this->hasOne(Organize::class, ['org_id' => 'org_id']);
    }
}
