<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_res_status".
 *
 * @property int $statusid
 * @property string $statusname
 */
class Resstatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_res_status';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['statusname'], 'required'],
            [['statusname'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'statusid' => 'Statusid',
            'statusname' => 'Statusname',
        ];
    }
}
