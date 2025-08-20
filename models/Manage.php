<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_manage".
 *
 * @property int $manageid
 * @property int $pro_id
 * @property int $manage
 * @property string $area
 * @property string $output
 * @property string $outcome
 * @property string $impact
 * @property string $dayup
 */
class Manage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_manage';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pro_id', 'manage', 'area', 'output', 'outcome', 'impact'], 'required'],
            [['pro_id', 'manage'], 'integer'],
            [['dayup'], 'safe'],
            [['area', 'output', 'outcome', 'impact'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'manageid' => 'Manageid',
            'pro_id' => 'Pro ID',
            'manage' => 'Manage',
            'area' => 'Area',
            'output' => 'Output',
            'outcome' => 'Outcome',
            'impact' => 'Impact',
            'dayup' => 'Dayup',
        ];
    }
}
