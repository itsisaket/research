<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $wc_id
 * @property string $ref_type
 * @property int $ref_id
 * @property string $username
 * @property string $role_code
 * @property int $sort_order
 * @property float|null $work_hours
 * @property float|null $contribution_pct
 * @property string|null $note
 * @property string $created_at
 * @property string|null $updated_at
 */
class WorkContributor extends ActiveRecord
{
    // สำหรับฟอร์ม select2 multiple (ไม่เก็บลง DB)
    public $usernames = [];   // array of usernames
    public $role_code_form;   // role ที่เลือกครั้งเดียวให้ทั้งชุด (optional)

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

            // ฟอร์ม multiple
            [['usernames'], 'required', 'on' => 'multi'],
            [['usernames'], 'each', 'rule' => ['string', 'max' => 50], 'on' => 'multi'],

            [['role_code_form'], 'string', 'max' => 20, 'on' => 'multi'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'ref_type' => 'โมดูล',
            'ref_id' => 'รหัสรายการ',
            'username' => 'ผู้ร่วม',
            'usernames' => 'เพิ่มผู้ร่วมหลายคน',
            'role_code' => 'บทบาท',
            'role_code_form' => 'บทบาทของชุดนี้',
            'sort_order' => 'ลำดับ',
            'work_hours' => 'ชั่วโมง',
            'contribution_pct' => 'สัดส่วน (%)',
            'note' => 'หมายเหตุ',
        ];
    }

    public static function refTypeItems()
    {
        return [
            'researchpro' => 'โครงการวิจัย',
            'article' => 'บทความ',
            'academic_service' => 'บริการวิชาการ',
            'utilization' => 'การนำไปใช้ประโยชน์',
        ];
    }
}
