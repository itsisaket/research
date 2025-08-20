<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_res_type".
 *
 * @property int $restypeid
 * @property string $restypename
 */
class Restype extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_res_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['restypename'], 'required'],
            [['restypename'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'restypeid' => 'Restypeid',
            'restypename' => 'Restypename',
        ];
    }
}
