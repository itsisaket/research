<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_position".
 *
 * @property int $positionid
 * @property string $positionname
 */
class Position extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_position';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['positionname'], 'required'],
            [['positionname'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'positionid' => 'Positionid',
            'positionname' => 'Positionname',
        ];
    }
}
