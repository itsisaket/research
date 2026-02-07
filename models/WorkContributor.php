<?php
namespace app\models;

use yii\db\ActiveRecord;

class WorkContributor extends ActiveRecord
{
    // สำหรับ select2 multiple
    public $usernames = [];
    public $role_code_form;

    // ✅ สำหรับกรอกสัดส่วนต่อ “ชุดที่เพิ่ม” (ค่าเดียวให้ทุกคนในรอบนั้น)
    public $pct_form;

    public static function tableName()
    {
        return 'work_contributor';
    }

    public function rules()
    {
        return [
            [['ref_type', 'ref_id'], 'required'],
            [['ref_id', 'sort_order'], 'integer'],
            [['work_hours', 'contribution_pct'], 'number'],
            [['ref_type'], 'string', 'max' => 30],
            [['username'], 'string', 'max' => 50],
            [['role_code'], 'string', 'max' => 20],
            [['note'], 'string', 'max' => 255],

            // ===== multi form =====
            [['usernames'], 'required', 'on' => 'multi'],
            [['usernames'], 'each', 'rule' => ['string', 'max' => 50], 'on' => 'multi'],
            [['role_code_form'], 'string', 'max' => 20, 'on' => 'multi'],

            // ✅ pct_form optional
            [['pct_form'], 'number', 'min' => 0, 'max' => 100, 'on' => 'multi'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'usernames' => 'ผู้ร่วมหลายคน',
            'role_code_form' => 'บทบาท',
            'pct_form' => 'สัดส่วน (%)',
        ];
    }
}
