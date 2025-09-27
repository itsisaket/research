<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_prefix".
 *
 * @property int $prefixid
 * @property string $prefixname
 */
class Prefix extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_prefix';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['prefixname'], 'required'],
            [['prefixname'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'prefixid' => 'Prefixid',
            'prefixname' => 'Prefixname',
        ];
    }
}
