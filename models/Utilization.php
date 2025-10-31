<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use app\models\Account;
use app\models\Organize;
use app\models\Utilization_type;
use app\models\Province;
use app\models\Amphur;
use app\models\District;

class Utilization extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'tb_utilization';
    }

    public function rules()
    {
        return [
            [
                [
                    'project_name',
                    'uid',
                    'org_id',
                    'utilization_type',
                    'utilization_add',
                    'sub_district',
                    'district',
                    'province',
                    'utilization_date'
                ],
                'required'
            ],
            [['uid', 'org_id', 'utilization_type', 'sub_district', 'district', 'province', 'research_id'], 'integer'],
            [['utilization_detail', 'utilization_refer', 'documentid'], 'string'],
            [['utilization_date'], 'safe'],
            [['utilization_add'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'utilization_id'   => 'รหัส',
            'project_name'     => 'โครงการวิจัย/งานสร้างสรรค์',
            'uid'              => 'นักวิจัย',
            'org_id'           => 'หน่วยงาน',
            'utilization_type' => 'ลักษณะของการใช้ประโยชน์',
            'utilization_add'  => 'หน่วยงานใช้ประโยชน์',
            'sub_district'     => 'ตำบล',
            'district'         => 'อำเภอ',
            'province'         => 'จังหวัด',
            'utilization_date' => 'วันที่ดำเนินการ',
            'utilization_detail' => 'การใช้ประโยชน์',
            'utilization_refer'  => 'ข้อมูลอ้างอิง',
            'research_id'         => 'งานวิจัย',
            'documentid'          => 'ไฟล์เอกสารแนบ',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // แปลง dd-mm-yyyy -> yyyy-mm-dd
            if (!empty($this->utilization_date) && strpos($this->utilization_date, '-') !== false) {
                $parts = explode('-', $this->utilization_date);
                if (count($parts) === 3) {
                    $this->utilization_date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            }
            return true;
        }
        return false;
    }

    /* ===== dropdown data ===== */

    public function getUserid()
    {
        $session = Yii::$app->session;
        $ty = $session->get('ty');

        $users = [];

        if (!Yii::$app->user->isGuest) {
            $identity = Yii::$app->user->identity;

            // เริ่มจาก user ตัวเอง
            $users = Account::find()->where(['uid' => $identity->uid])->all();

            // ถ้าไม่ใช่สิทธิ์พื้นฐาน → ให้เห็นเฉพาะ org
            if ($identity->position != 1) {
                $users = Account::find()
                    ->where(['org_id' => $ty])
                    ->orderBy(['uname' => SORT_ASC])
                    ->all();
            }

            // ถ้าเป็น admin
            if ($identity->position == 4) {
                $users = Account::find()
                    ->orderBy(['uname' => SORT_ASC])
                    ->all();
            }
        }

        return ArrayHelper::map($users, 'uid', function ($user) {
            return trim($user->uname . ' ' . $user->luname);
        });
    }

    public function getOrgid()
    {
        $session = Yii::$app->session;
        $ty = $session->get('ty');

        if ((int)$ty === 11) {
            return ArrayHelper::map(Organize::find()->all(), 'org_id', 'org_name');
        }

        return ArrayHelper::map(
            Organize::find()->where(['org_id' => $ty])->all(),
            'org_id',
            'org_name'
        );
    }

    public function getUtilizationtype()
    {
        return ArrayHelper::map(
            Utilization_type::find()->all(),
            'utilization_type',
            'utilization_type_name'
        );
    }

    /* ===== relations ===== */

    public function getUser()
    {
        return $this->hasOne(Account::class, ['uid' => 'uid']);
    }

    public function getHasorg()
    {
        return $this->hasOne(Organize::class, ['org_id' => 'org_id']);
    }

    public function getUtilization()
    {
        return $this->hasOne(Utilization_type::class, ['utilization_type' => 'utilization_type']);
    }

    public function getDist()
    {
        return $this->hasOne(District::class, ['DISTRICT_CODE' => 'sub_district']);
    }

    public function getAmph()
    {
        return $this->hasOne(Amphur::class, ['AMPHUR_CODE' => 'district']);
    }

    public function getProv()
    {
        // ⚠ ใช้ PROVINCE_ID ให้ตรงกับ view
        return $this->hasOne(Province::class, ['PROVINCE_ID' => 'province']);
    }

    /* ===== display helper ===== */

    public function getProvinceName()
    {
        return $this->prov->PROVINCE_NAME ?? '';
    }

    public function getAmphurName()
    {
        return $this->amph->AMPHUR_NAME ?? '';
    }

    public function getDistrictName()
    {
        return $this->dist->DISTRICT_NAME ?? '';
    }
}
