<?php

namespace app\models;

use Yii;
use app\models\Organize;
use app\models\Position;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "tb_user".
 *
 * @property int $uid
 * @property string $username
 * @property string $password
 * @property string $password_reset_token
 * @property string $authKey
 * @property int $prefix
 * @property string $uname
 * @property string $luname
 * @property int $org_id
 * @property string $email
 * @property int $tel
 * @property int $academic
 * @property int $position
 * @property string $dayup
 */
class Account extends \yii\db\ActiveRecord 
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'password', 'prefix', 'uname', 'luname', 'org_id', 'email', 'tel'], 'required'],
            [['prefix', 'org_id','position'], 'integer'],
            [['dayup'], 'safe'],
            [['username', 'password', 'password_reset_token', 'authKey', 'email'], 'string', 'max' => 50],
            [['uname', 'luname'], 'string', 'max' => 100],
            [['username'], 'match','pattern' => '/^[a-zA-Z0-9]*$/i','message' => 'Invalid characters in username.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'Uid',
            'username' => 'Username',
            'password' => 'Password',
            'password_reset_token' => 'Password Reset Token',
            'authKey' => 'Auth Key',
            'prefix' => 'คำนำหน้า',
            'uname' => 'ชื่อสมาชิก',
            'luname' => 'นามสกุล',
            'org_id' => 'หน่วยงานสังกัด',
            'email' => 'Email',
            'tel' => 'เบอร์ติดต่อ',
            'position' => 'สถานะ',
            'dayup' => 'Dayup',
        ];
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
    public function getPositions(){  
        $session = Yii::$app->session;
        $ty=$session['ty'];
        
        if (!Yii::$app->user->isGuest){
            $Positions = Position::find()->where(['positionid'=>Yii::$app->user->identity->position])->all();
            if (Yii::$app->user->identity->position != 1) {
                $Positions = Position::find()->where(['positionid'=>1])->orwhere(['positionid'=>2])->orderBy('positionid')->all();
            }
            if (Yii::$app->user->identity->position == 4) {
                $Positions = Position::find()->orderBy('positionid')->all();
            }
        }
        $PositionList  = [];
        $PositionList = ArrayHelper::map($Positions, 'positionid', function ($Position) {
            return $Position->positionname;
         }); 
         return $PositionList;

        //return ArrayHelper::map(Position::find()->all(),'positionid','positionname'); 
    }


    public function getHasprefix()
    {
        return $this->hasOne(Prefix::className(), ['prefixid' => 'prefix']);
    }   
    public function getHasorg()
    {
        return $this->hasOne(Organize::className(), ['org_id' => 'org_id']);
    }
    public function getHasposition()
    {
        return $this->hasOne(Position::className(), ['positionid' => 'position']);
    }




}
