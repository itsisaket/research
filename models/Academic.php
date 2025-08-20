<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_academic".
 *
 * @property int $academicid
 * @property string $academicname
 * @property string $academiccode
 */
class Academic extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_academic';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['academicname', 'academiccode'], 'required'],
            [['academicname', 'academiccode'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'academicid' => 'Academicid',
            'academicname' => 'Academicname',
            'academiccode' => 'Academiccode',
        ];
    }
}
