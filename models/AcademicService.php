<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

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
            [['username', 'service_date', 'type_id', 'title', 'hours'], 'required'],

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


   public function getUserid()
    {
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        $users = [];

        if (!Yii::$app->user->isGuest) {

            // ปกติ: ให้เห็นเฉพาะตัวเอง
            $users = Account::find()
                ->where(['username' => Yii::$app->user->identity->username])
                ->all();

            // ถ้าไม่ใช่นักวิจัย (position != 1) → เห็นเฉพาะหน่วยงานตัวเอง
            if ((int)Yii::$app->user->identity->position !== 1 && $ty) {
                $users = Account::find()
                    ->where(['org_id' => $ty])
                    ->orderBy(['uname' => SORT_ASC])
                    ->all();
            }

            // admin (position == 4) → เห็นทั้งหมด
            if ((int)Yii::$app->user->identity->position === 4) {
                $users = Account::find()
                    ->orderBy(['uname' => SORT_ASC])
                    ->all();
            }
        }

        $userList = ArrayHelper::map($users, 'username', function ($user) {

            $fn = trim((string)($user->uname ?? ''));
            $ln = trim((string)($user->luname ?? ''));

            $full = trim($fn . ' ' . $ln);

            // ❌ กันกรณีชื่อเป็น email / มี @
            if ($full === '' || strpos($full, '@') !== false) {
                // ใช้รหัสบุคลากรแทน (username ของ Account)
                return 'รหัสบุคลากร: ' . $user->username;
            }

            return $full;
        });

        return $userList;
    }

    public function getOrgid()
    {
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;

        if ($ty == 11) {
            return ArrayHelper::map(Organize::find()->all(), 'org_id', 'org_name');
        }
        if ($ty) {
            return ArrayHelper::map(Organize::find()->where(['org_id' => $ty])->all(), 'org_id', 'org_name');
        }

        // fallback ถ้าไม่มี ty
        return ArrayHelper::map(Organize::find()->all(), 'org_id', 'org_name');
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
        $name = trim(($u->uname ?? '') . ' ' . ($u->luname ?? ''));
        return $name !== '' ? $name : $this->username;
    }
    public function getUser()
    {
        return $this->hasOne(Account::class, ['username' => 'username']);
    }
}
