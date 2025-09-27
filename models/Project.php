<?php

namespace app\models;
use yii\helpers\ArrayHelper;

use Yii;
use app\models\Province;
/**
 * This is the model class for table "tb_project".
 *
 * @property int $pro_id
 * @property string $pro_name
 * @property int $uid
 * @property int $pro_position
 * @property int $pro_capital
 * @property int $pro_type
 * @property int $pro_year
 * @property int $pro_budget
 * @property int $pro_status
 * @property string $pro_keyword
 * @property string $pro_location
 * @property int $sub_district
 * @property int $district
 * @property int $province
 * @property string $dayup
 */
class Project extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_project';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pro_name', 'uid', 'pro_position', 'pro_capital', 'pro_type', 'pro_year', 'pro_budget', 'pro_status', 'pro_location', 'sub_district', 'district', 'province'], 'required'],
            [['uid', 'pro_position', 'pro_type', 'pro_year', 'pro_budget', 'pro_status', 'sub_district', 'district', 'province'], 'integer'],
            [['dayup'], 'safe'],
            [['pro_keyword', 'pro_location'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'pro_id' => 'Pro ID',
            'pro_name' => 'ชื่อโครงการ',
            'uid' => 'เจ้าของโครงการ',
            'pro_position' => 'ตำแหน่งนักวิจัย',
            'pro_capital' => 'แหล่งทุน',
            'pro_type' => 'ประเภทโครงการ',
            'pro_year' => 'ปีงบประมาณ',
            'pro_budget' => 'งบประมาณ',
            'pro_status' => 'สภานะ',
            'pro_keyword' => 'Keyword',
            'pro_location' => 'พื้นที่วิจัย',
            'sub_district' => 'ตำบล',
            'district' => 'อำเภอ',
            'province' => 'จังหวัด',
            'dayup' => 'วันที่อัพ',
        ];
    }
    public function getUserid(){  
        $userList  = [];
        $users = Account::find()->orderBy('uid')->all();
        $userList = ArrayHelper::map($users, 'uid', function ($user) {
            return $user->uname.' '.$user->luname;
         }); 
         return $userList;

    }     
    public function getPositionid(){  
        return ArrayHelper::map(Resposition::find()->all(),'res_positionid','res_positionname'); 
    }
    
    public function getCapitalid(){  
        return ArrayHelper::map(Capital::find()->all(),'capitalid','capitalname'); 
    }
    
    public function getResstatusid(){  
        return ArrayHelper::map(Resstatus::find()->all(),'statusid','statusname'); 
    }

    public function getRestypeid(){  
        return ArrayHelper::map(Restype::find()->all(),'restypeid','restypename'); 
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
    public function getUser()
    {
        return $this->hasOne(Account::className(), ['uid' => 'uid']);
    }
    public function getPosition()
    {
        return $this->hasOne(Resposition::className(), ['res_positionid' => 'pro_position']);
    }    
    public function getCapital()
    {
        return $this->hasOne(Capital::className(), ['capitalid' => 'pro_capital']);
    }
    public function getResstatus()
    {
        return $this->hasOne(Resstatus::className(), ['statusid' => 'pro_status']);
    }
    public function getRestype()
    {
        return $this->hasOne(Restype::className(), ['restypeid' => 'pro_type']);
    }

}
