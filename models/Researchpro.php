<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use app\models\Province;

/**
 * This is the model class for table "tb_researchpro".
 *
 * @property int $projectID รหัสโครงการ
 * @property string $projectNameTH ชื่อโครงการภาษาไทย
 * @property string $projectNameEN ชื่อโครงการภาษาอังกฤษ
 * @property int $uid หัวหน้าโครงการ
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
            [['projectNameTH', 'projectNameEN', 'uid', 'org_id', 'projectYearsubmit', 'budgets', 'fundingAgencyID', 'researchFundID', 'researchTypeID', 'projectStartDate', 'projectEndDate', 'jobStatusID', 'researchArea', 'sub_district', 'district', 'province','branch'], 'required'],
            [['uid', 'org_id', 'projectYearsubmit', 'budgets', 'fundingAgencyID', 'researchFundID', 'researchTypeID', 'jobStatusID', 'sub_district', 'district', 'province','branch'], 'integer'],
            [['projectStartDate', 'projectEndDate'], 'safe'],
            [['projectNameTH', 'projectNameEN'], 'string', 'max' => 100],
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
            'uid' => 'หัวหน้าโครงการ',
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

    public function getUserid(){  
        $session = Yii::$app->session;
        $ty=$session['ty'];
        
        if (!Yii::$app->user->isGuest){
            $users = Account::find()->where(['uid'=>Yii::$app->user->identity->uid])->all();
            if (Yii::$app->user->identity->position !=1) {
                $users = Account::find()->where(['org_id'=>$ty])->orderBy('uname')->all();
            }
            if (Yii::$app->user->identity->position == 4) {
                $users = Account::find()->orderBy('uname')->all();
            }
        }
        $userList  = [];
        $userList = ArrayHelper::map($users, 'uid', function ($user) {
            return $user->uname.' '.$user->luname;
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
        return $this->hasOne(Account::className(), ['uid' => 'uid']);
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
