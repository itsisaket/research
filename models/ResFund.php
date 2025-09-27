<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_res_fund".
 *
 * @property int $researchFundID
 * @property string $researchFundName
 */
class ResFund extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_res_fund';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['researchFundName'], 'required'],
            [['researchFundName'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'researchFundID' => 'Research Fund ID',
            'researchFundName' => 'Research Fund Name',
        ];
    }
}
