<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_publication".
 *
 * @property int $publication_type รหัสฐานทุน
 * @property string $publication_name ชื่อฐานทุน
 * @property string $publication_detail รายละเอียด
 */
class Publication extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_publication';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['publication_name', 'publication_detail'], 'required'],
            [['publication_detail'], 'string'],
            [['publication_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'publication_type' => 'รหัสฐานทุน',
            'publication_name' => 'ชื่อฐานทุน',
            'publication_detail' => 'รายละเอียด',
        ];
    }
}
