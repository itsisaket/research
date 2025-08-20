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
 * @property int $uid นักวิจัย
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
            [['article_th', 'article_eng', 'uid', 'org_id', 'publication_type', 'article_publish', 'journal','status_ec','branch'], 'required'],
            [['uid', 'org_id', 'publication_type','research_id','status_ec','branch'], 'integer'],
            [['article_publish','refer','documentid'], 'string'],
            [['article_th', 'article_eng', 'journal'], 'string', 'max' => 200],
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
            'uid' => 'นักวิจัย',
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
        return $this->hasOne(Account::className(), ['uid' => 'uid']);
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
