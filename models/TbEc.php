<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_ec".
 *
 * @property int $status_ec ec_id
 * @property string $ec_name จริยธรรมในมนุษย์
 */
class TbEc extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_ec';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ec_name'], 'required'],
            [['ec_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'status_ec' => 'ec_id',
            'ec_name' => 'จริยธรรมในมนุษย์',
        ];
    }
}
