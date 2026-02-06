<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "academic_service_type".
 *
 * @property int $type_id
 * @property string $type_name
 * @property int $is_active
 * @property int $sort_order
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property AcademicService[] $academicServices
 */
class AcademicServiceType extends ActiveRecord
{
    public static function tableName()
    {
        return 'academic_service_type';
    }

    public function rules()
    {
        return [
            [['type_name'], 'required'],
            [['is_active', 'sort_order'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['type_name'], 'string', 'max' => 150],

            [['is_active'], 'default', 'value' => 1],
            [['sort_order'], 'default', 'value' => 100],

            [['type_name'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'type_id'    => 'รหัสประเภท',
            'type_name'  => 'ประเภทบริการวิชาการ',
            'is_active'  => 'สถานะใช้งาน',
            'sort_order' => 'ลำดับ',
            'created_at' => 'สร้างเมื่อ',
            'updated_at' => 'แก้ไขเมื่อ',
        ];
    }

    public function getAcademicServices()
    {
        return $this->hasMany(AcademicService::class, ['type_id' => 'type_id']);
    }

    /** ใช้ทำ dropdown ได้เลย */
    public static function getItems($onlyActive = true)
    {
        $q = static::find()->orderBy(['sort_order' => SORT_ASC, 'type_name' => SORT_ASC]);
        if ($onlyActive) {
            $q->andWhere(['is_active' => 1]);
        }
        $rows = $q->all();

        $items = [];
        foreach ($rows as $r) {
            $items[$r->type_id] = $r->type_name;
        }
        return $items;
    }
}
