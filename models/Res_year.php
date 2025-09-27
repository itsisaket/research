<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_res_year".
 *
 * @property int $res_year
 */
class Res_year extends \yii\db\ActiveRecord
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
            [['res_year'], 'required'],
            [['res_year'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'res_year' => 'Res Year',
        ];
    }
}
