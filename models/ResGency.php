<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_res_gency".
 *
 * @property int $fundingAgencyID
 * @property string $fundingAgencyName
 * @property string $fundingAgency
 */
class ResGency extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_res_gency';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fundingAgencyID','fundingAgencyName', 'fundingAgency'], 'required'],
            [['fundingAgencyName', 'fundingAgency'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'fundingAgencyID' => 'Funding Agency ID',
            'fundingAgencyName' => 'Funding Agency Name',
            'fundingAgency' => 'Funding Agency',
        ];
    }
}
