<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tb_document".
 *
 * @property int $docuid
 * @property int $pro_id
 * @property string $docu_name
 * @property string $document
 */
class Document extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_document';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pro_id', 'docu_name', 'document'], 'required'],
            [['pro_id'], 'integer'],
            [['docu_name', 'document'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'docuid' => 'Docuid',
            'pro_id' => 'Pro ID',
            'docu_name' => 'Docu Name',
            'document' => 'Document',
        ];
    }
}
