<?php

namespace app\models;


use Yii;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "tb_article".
 *
 * @property int $article_id รหัสบทความ
 * @property string $article_th ชื่อบทความ(ไทย)
 * @property string $article_eng ชื่อบทความ(Eng)
 * @property int $username นักวิจัย
 * @property int $org_id หน่วยงาน
 * @property int $publication_type ประเภทฐาน
 * @property string $article_publish วันที่เผยแพร่
 * @property string $journal วารสาร/งานประชุม
 * @property string $refer อ้างอิง
 * @property int $research_id บทความวิจัย
 * @property int $status_ec จริยธรรมในมนุษย์
 * @property int $branch สาขาวิชา
 * @property string $documentid ไฟล์เอกสารแนบ
 */
class Article extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_article';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['article_th', 'article_eng', 'username', 'org_id', 'publication_type', 'article_publish', 'journal','status_ec','branch'], 'required'],
            [['username', 'org_id', 'publication_type','research_id','status_ec','branch'], 'integer'],
            [['article_publish','refer','documentid'], 'string'],
            [['article_th', 'article_eng', 'journal'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'article_id' => 'รหัสบทความ',
            'article_th' => 'ชื่อบทความ(ไทย)',
            'article_eng' => 'ชื่อบทความ(Eng)',
            'username' => 'ผู้บันทึก/เจ้าของเรื่อง',
            'org_id' => 'หน่วยงาน',
            'publication_type' => 'ประเภทฐาน',
            'article_publish' => 'วันที่เผยแพร่',
            'journal' => 'วารสาร/งานประชุม',
            'refer' => 'อ้างอิง',
            'research_id' => 'บทความวิจัย',
            'status_ec' => 'จริยธรรมในมนุษย์',
            'branch' => 'สาขาวิชา',
            'documentid' => 'ไฟล์เอกสารแนบ',
        ];
    }

    /**
     * ✅ Normalize article_publish เป็น ISO Y-m-d ก่อนบันทึกเสมอ
     *
     * รองรับ input จากฟอร์ม/import ในรูปแบบ:
     *   - DD-MM-YYYY (จาก DatePicker)
     *   - DD/MM/YYYY
     *   - YYYY-MM-DD (ISO อยู่แล้ว — ปล่อยผ่าน)
     *   - YYYY-MM-DD HH:MM:SS (ตัดเหลือเฉพาะ DATE)
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!empty($this->article_publish)) {
            $this->article_publish = self::normalizeIsoDate((string)$this->article_publish);
        }

        return true;
    }

    /**
     * แปลง string วันที่ (หลาย format) → 'Y-m-d'
     * ถ้า parse ไม่ได้ คืนค่าเดิม (ให้ Yii validation จัดการ)
     */
    public static function normalizeIsoDate(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';

        // ตัดเวลาออก (ถ้ามี)
        if (strpos($s, ' ') !== false) {
            $s = explode(' ', $s)[0];
        }

        // ถ้าเป็น ISO อยู่แล้ว ไม่ต้องแปลง
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s;
        }

        // ลอง parse format ที่รู้จัก
        foreach (['d-m-Y', 'd/m/Y', 'Y/m/d', 'd.m.Y'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $s);
            if ($dt !== false) {
                $errors = \DateTime::getLastErrors();
                // PHP 8.2+ คืน false ถ้าไม่มี error ส่วน <8.2 คืน array ที่ warning_count
                if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                    continue;
                }
                return $dt->format('Y-m-d');
            }
        }

        // fallback: strtotime (รองรับ d-m-Y ตาม locale ยุโรป)
        $ts = strtotime($s);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return $s; // คืนค่าเดิม → Yii validation จะ reject ถ้าจำเป็น
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

    public function getOrgid(){  
        $session = Yii::$app->session;
        $ty=$session['ty'];
        if ($ty==11) {
            return ArrayHelper::map(Organize::find()->all(),'org_id','org_name'); 
        }else{
            return ArrayHelper::map(Organize::find()->where(['org_id'=>$ty])->all(),'org_id','org_name'); 
        }
    }
    public function getPublication(){  
        return ArrayHelper::map(Publication::find()->all(),'publication_type','publication_name'); 
    }
    public function getBranch(){  
        return ArrayHelper::map(TbBranch::find()->all(),'branch_id','branch_name'); 
    }
    public function getEc(){  
        return ArrayHelper::map(TbEc::find()->all(),'status_ec','ec_name'); 
    }

    public function getUser()
    {
        return $this->hasOne(Account::className(), ['username' => 'username']);
    }
    public function getHasorg()
    {
        return $this->hasOne(Organize::className(), ['org_id' => 'org_id']);
    }  
    public function getPubli()
    {
        return $this->hasOne(Publication::className(), ['publication_type' => 'publication_type']);
    }    
    public function getHabranch()
    {
        return $this->hasOne(TbBranch::className(), ['branch_id' => 'branch']);
    }    
    public function getHaec()
    {
        return $this->hasOne(TbEc::className(), ['status_ec' => 'status_ec']);
    }    
}
