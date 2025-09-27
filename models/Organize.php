<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_organize".
 *
 * @property int $org_id
 * @property string $org_name
 * @property string $org_logo
 */
class Organize extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_organize';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['org_name', 'org_logo'], 'required'],
            [['org_name', 'org_logo'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'org_id' => 'Org ID',
            'org_name' => 'Org Name',
            'org_logo' => 'Org Logo',
        ];
    }
}
