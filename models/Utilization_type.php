<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_utilization_type".
 *
 * @property int $utilization_type รหัส
 * @property string $utilization_type_name การใช้ประโยชน์
 */
class Utilization_type extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_utilization_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['utilization_type_name'], 'required'],
            [['utilization_type_name'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'utilization_type' => 'รหัส',
            'utilization_type_name' => 'การใช้ประโยชน์',
        ];
    }
}
