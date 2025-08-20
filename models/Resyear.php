<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_resyear".
 *
 * @property int $resyear
 */
class Resyear extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_resyear';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['resyear'], 'required'],
            [['resyear'], 'integer'],
            [['resyear'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'resyear' => 'Resyear',
        ];
    }
}
