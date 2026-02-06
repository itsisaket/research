<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "academic_service".
 *
 * @property int $service_id
 * @property string $username
 * @property int|null $org_id
 * @property string $service_date
 * @property int $type_id
 * @property string $title
 * @property string|null $location
 * @property string|null $work_desc
 * @property float $hours
 * @property string|null $reference_url
 * @property string|null $attachment_path
 * @property int $status
 * @property string|null $note
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Account $user
 * @property AcademicServiceType $serviceType
 */
class AcademicService extends ActiveRecord
{
    public static function tableName()
    {
        return 'academic_service';
    }

    public function rules()
    {
        return [
            // required
            [['service_date', 'type_id', 'title', 'hours'], 'required'],

            // types
            [['type_id', 'org_id', 'status'], 'integer'],
            [['work_desc'], 'string'],
            [['hours'], 'number', 'min' => 0],
            [['service_date', 'created_at', 'updated_at'], 'safe'],

            // strings
            [['username'], 'string', 'max' => 50],
            [['title', 'location'], 'string', 'max' => 255],
            [['reference_url', 'attachment_path'], 'string', 'max' => 500],
            [['note'], 'string', 'max' => 255],

            // defaults
            [['status'], 'default', 'value' => 1],

            // FK exists checks (ถ้าตาราง/โมเดลชื่อไม่ตรง ปรับตรงนี้)
            [['type_id'], 'exist', 'skipOnError' => true,
                'targetClass' => AcademicServiceType::class,
                'targetAttribute' => ['type_id' => 'type_id']
            ],
            [['username'], 'exist', 'skipOnError' => true,
                'targetClass' => Account::class,
                'targetAttribute' => ['username' => 'username']
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'service_id'      => 'รหัสรายการ',
            'username'        => 'ผู้บันทึก/เจ้าของเรื่อง',
            'org_id'          => 'หน่วยงาน',
            'service_date'    => 'วันที่ปฏิบัติงาน',
            'type_id'         => 'ประเภทบริการวิชาการ',
            'title'           => 'เรื่อง',
            'location'        => 'สถานที่',
            'work_desc'       => 'ลักษณะงาน',
            'hours'           => 'จำนวนชั่วโมงทำงาน',
            'reference_url'   => 'ลิงก์/อ้างอิง',
            'attachment_path' => 'ไฟล์แนบ',
            'status'          => 'สถานะ',
            'note'            => 'หมายเหตุ',
            'created_at'      => 'สร้างเมื่อ',
            'updated_at'      => 'แก้ไขเมื่อ',
        ];
    }

    /** auto-set owner + org_id เมื่อ create */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if ($this->isNewRecord) {
            $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
            if ($me && empty($this->username) && !empty($me->username)) {
                $this->username = (string)$me->username;
            }
            if ($me && empty($this->org_id) && !empty($me->org_id)) {
                $this->org_id = (int)$me->org_id;
            }
        }

        return true;
    }

    /** ความสัมพันธ์: เจ้าของรายการ */
    public function getUser()
    {
        return $this->hasOne(Account::class, ['username' => 'username']);
    }

    /** ความสัมพันธ์: ประเภทบริการวิชาการ */
    public function getServiceType()
    {
        return $this->hasOne(AcademicServiceType::class, ['type_id' => 'type_id']);
    }

    /** helper: แสดงชื่อเต็ม */
    public function getOwnerFullname()
    {
        $u = $this->user;
        if (!$u) return $this->username;
        $name = trim(($u->prefix ?? '') . ($u->uname ?? '') . ' ' . ($u->luname ?? ''));
        return $name !== '' ? $name : $this->username;
    }
}
