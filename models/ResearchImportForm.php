<?php

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class ResearchImportForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $file;

    public function rules()
    {
        return [
            [['file'], 'required'],
            [
                'file',
                'file',
                'extensions' => ['xls', 'xlsx'],
                'checkExtensionByMimeType' => true,
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => 'ไฟล์ Excel',
        ];
    }
}
