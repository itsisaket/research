<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use app\models\Province;
use app\models\WorkContributor;

/**
 * This is the model class for table "tb_researchpro".
 *
 * @property int $projectID รหัสโครงการ
 * @property string $projectNameTH ชื่อโครงการภาษาไทย
 * @property string $projectNameEN ชื่อโครงการภาษาอังกฤษ
 * @property int $username นักวิจัย
 * @property int $org_id หน่วยงาน
 * @property int $projectYearsubmit ปีงบประมาณ
 * @property int $budgets งบประมาณ
 * @property int $fundingAgencyID รหัสแหล่งทุน
 * @property int $researchFundID ประเภททุนวิจัย
 * @property int $researchTypeID รหัสประเภทการวิจัย
 * @property string $projectStartDate วันที่เริ่มต้นโครงการ
 * @property string $projectEndDate วันที่สิ้นสุดโครงการ
 * @property int $jobStatusID รหัสสถานะงาน
 * @property string $researchArea พื้นที่วิจัย
 * @property int $sub_district ตำบล
 * @property int $district อำเภอ
 * @property int $province จังหวัด
 * @property int $branch สาขาวิชา
 * @property string $documentid ไฟล์เอกสารแนบ
 */
class Researchpro extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_researchpro';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['projectNameTH', 'projectNameEN', 'username', 'org_id', 'projectYearsubmit', 'budgets', 'fundingAgencyID', 'researchFundID', 'researchTypeID', 'projectStartDate', 'projectEndDate', 'jobStatusID', 'researchArea', 'sub_district', 'district', 'province','branch'], 'required'],
            [['username', 'org_id', 'projectYearsubmit', 'budgets', 'fundingAgencyID', 'researchFundID', 'researchTypeID', 'jobStatusID', 'sub_district', 'district', 'province','branch'], 'integer'],
            [['projectStartDate', 'projectEndDate'], 'safe'],
            [['projectNameTH', 'projectNameEN'], 'string'],
            [['researchArea','documentid'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'projectID' => 'รหัสโครงการ',
            'projectNameTH' => 'ชื่อโครงการภาษาไทย',
            'projectNameEN' => 'ชื่อโครงการภาษาอังกฤษ',
            'username' => 'ผู้บันทึก/เจ้าของเรื่อง',
            'org_id' => 'หน่วยงาน',
            'projectYearsubmit' => 'ปีงบประมาณ',
            'budgets' => 'งบประมาณ',
            'fundingAgencyID' => 'แหล่งทุน',
            'researchTypeID' => 'ประเภทโครงการ',
            'projectStartDate' => 'วันที่เริ่มต้นโครงการ',
            'projectEndDate' => 'วันที่สิ้นสุดโครงการ',
            'researchFundID' => 'ประเภทการวิจัย',
            'jobStatusID' => 'สถานะงาน',
            'researchArea' => 'พื้นที่วิจัย',
            'sub_district' => 'ตำบล',
            'district' => 'อำเภอ',
            'province' => 'จังหวัด',
            'branch' => 'สาขาวิชา',
            'documentid' => 'ไฟล์เอกสารแนบ',

        ];
    }

    /**
     * ✅ Auto-add ผู้บันทึก/เจ้าของเรื่อง เป็นผู้ร่วมโครงการอัตโนมัติ
     *    เมื่อสร้างโครงการใหม่ — ใช้ role 'leader' (หัวหน้าโครงการ)
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert && !empty($this->username)) {
            $exists = WorkContributor::find()
                ->where([
                    'ref_type' => 'researchpro',
                    'ref_id'   => (int)$this->projectID,
                    'username' => (string)$this->username,
                ])
                ->exists();

            if (!$exists) {
                $wc = new WorkContributor();
                $wc->ref_type         = 'researchpro';
                $wc->ref_id           = (int)$this->projectID;
                $wc->username         = (string)$this->username;
                $wc->role_code        = 'leader';   // หัวหน้าโครงการ
                $wc->contribution_pct = 100.0;
                $wc->sort_order       = 1;
                @$wc->save(false);
            }
        }
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
    public function getYears(){  
        return ArrayHelper::map(Res_year::find()->orderBy('resyear')->all(),'resyear','resyear'); 
    }
    public function getRestype(){  
        return ArrayHelper::map(Restype::find()->all(),'restypeid','restypename'); 
    }
    public function getResstatus(){  
        return ArrayHelper::map(Resstatus::find()->all(),'statusid','statusname'); 
    }
    public function getBranch(){  
        return ArrayHelper::map(TbBranch::find()->all(),'branch_id','branch_name'); 
    }
    public function getResFund(){  
        return ArrayHelper::map(ResFund::find()->all(),'researchFundID','researchFundName'); 
    }
    public function getResAgency(){  
        return ArrayHelper::map(ResGency::find()->all(),'fundingAgencyID','fundingAgencyName'); 
    }

    public function getUser()
    {
        return $this->hasOne(Account::className(), ['username' => 'username']);
    }
    public function getHasorg()
    {
        return $this->hasOne(Organize::className(), ['org_id' => 'org_id']);
    }       
    public function getDist()
    {
        return $this->hasOne(District::className(), ['DISTRICT_CODE' => 'sub_district']);
    }
    public function getAmph()
    {
        return $this->hasOne(Amphur::className(), ['AMPHUR_CODE' => 'district']);
    }
    public function getProv()
    {
        return $this->hasOne(Province::className(), ['PROVINCE_CODE' => 'province']);
    }
    public function getRestypes(){  
        return $this->hasOne(Restype::className(),['restypeid' =>'researchTypeID']); 
    }
    public function getResstatuss(){  
        return $this->hasOne(Resstatus::className(),['statusid' =>'jobStatusID']); 
    }
    public function getHabranchs()
    {
        return $this->hasOne(TbBranch::className(), ['branch_id' => 'branch']);
    } 
    public function getResFunds(){  
        return $this->hasOne(ResFund::className(),['researchFundID' =>'researchFundID']); 
    }
    public function getAgencys(){  
        return $this->hasOne(ResGency::className(),['fundingAgencyID' =>'fundingAgencyID']); 
    }
}
