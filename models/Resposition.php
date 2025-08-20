<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_res_position".
 *
 * @property int $res_positionid
 * @property string $res_positionname
 */
class Resposition extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_res_position';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['res_positionname'], 'required'],
            [['res_positionname'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'res_positionid' => 'Res Positionid',
            'res_positionname' => 'Res Positionname',
        ];
    }
}
