<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_capital".
 *
 * @property int $capitalid
 * @property string $capitalname
 * @property string $capitaltype
 */
class Capital extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_capital';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['capitalname', 'capitaltype'], 'required'],
            [['capitalname'], 'string', 'max' => 50],
            [['capitaltype'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'capitalid' => 'Capitalid',
            'capitalname' => 'Capitalname',
            'capitaltype' => 'Capitaltype',
        ];
    }
}
