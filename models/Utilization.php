<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use app\models\Province;
/**
 * This is the model class for table "tb_utilization".
 *
 * @property int $utilization_id รหัส
 * @property string $project_name โครงการวิจัย/งานสร้างสรรค์
 * @property int $uid นักวิจัย
 * @property int $org_id หน่วยงาน
 * @property int $utilization_type ลักษณะของการใช้ประโยชน์
 * @property string $utilization_add หน่วยงานใช้ประโยชน์
 * @property int $sub_district ตำบล
 * @property int $district อำเภอ
 * @property int $province จังหวัด
 * @property string $utilization_date วันที่ดำเนินการ
 * @property string $utilization_detail การใช้ประโยชน์
 * @property string $utilization_refer อ้างอิง
 * @property int $research_id งานวิจัย
 * @property string $documentid ไฟล์เอกสารแนบ
 */
class Utilization extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_utilization';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['project_name', 'uid', 'org_id', 'utilization_type', 'utilization_add', 'sub_district', 'district', 'province', 'utilization_date'], 'required'],
            [['uid', 'org_id', 'utilization_type', 'sub_district', 'district', 'province','research_id'], 'integer'],
            [['utilization_detail', 'utilization_refer', 'documentid','utilization_date'], 'string'],
            [['utilization_add'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'utilization_id' => 'รหัส',
            'project_name' => 'โครงการวิจัย/งานสร้างสรรค์',
            'uid' => 'นักวิจัย',
            'org_id' => 'หน่วยงาน',
            'utilization_type' => 'ลักษณะของการใช้ประโยชน์',
            'utilization_add' => 'หน่วยงานใช้ประโยชน์',
            'sub_district' => 'ตำบล',
            'district' => 'อำเภอ',
            'province' => 'จังหวัด',
            'utilization_date' => 'วันที่ดำเนินการ',
            'utilization_detail' => 'การใช้ประโยชน์',
            'utilization_refer' => 'ข้อมูลอ้างอิง',
            'research_id' => 'งานวิจัย',
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
    public function getUtilizationtype(){  

            return ArrayHelper::map(Utilization_type::find()->all(),'utilization_type','utilization_type_name'); 
       
    }

    public function getUser()
    {
        return $this->hasOne(Account::className(), ['uid' => 'uid']);
    }     
    public function getHasorg()
    {
        return $this->hasOne(Organize::className(), ['org_id' => 'org_id']);
    }  
    public function getUtilization()
    {
        return $this->hasOne(Utilization_type::className(), ['utilization_type' => 'utilization_type']);
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
}
