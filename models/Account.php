<?php

namespace app\models;

use Yii;
use app\models\Organize;
use app\models\Position;
use yii\helpers\ArrayHelper;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

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
class Account extends \yii\db\ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tb_user';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => null,
                'updatedAtAttribute' => 'dayup',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function scenarios()
    {
        $sc = parent::scenarios();

        // แบบฟอร์มปกติ (ไม่บังคับ password แล้ว)
        $sc['default'] = [
            'username','password','prefix','uname','luname','org_id','email','tel',
            'position','password_reset_token','authKey','dayup'
        ];

        // ซิงก์จาก SSO/JWT
        $sc['ssoSync'] = [
            'username','prefix','uname','luname','org_id','email','tel','position',
            'password_reset_token','authKey','dayup'
        ];

        return $sc;
    }

    public function rules()
    {
        return [
            // ต้องกรอกพื้นฐาน (ไม่รวม password)
            [['username', 'prefix', 'uname', 'luname', 'org_id', 'email', 'tel'], 'required'],

            // อนุญาต password ว่างได้ -> แปลง '' เป็น NULL
            ['password', 'filter', 'filter' => function($v){ return $v === '' ? null : $v; }],

            [['prefix', 'org_id', 'position'], 'integer'],
            [['dayup'], 'safe'],
            [['username', 'password', 'password_reset_token', 'authKey', 'email'], 'string', 'max' => 50],
            [['uname', 'luname'], 'string', 'max' => 100],
            [['username'], 'match','pattern' => '/^[a-zA-Z0-9]*$/i','message' => 'Invalid characters in username.'],

            // กันข้อมูลซ้ำ
            [['username'], 'unique'],
            [['email'], 'unique'],
        ];
    }

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
            'dayup' => 'ปรับปรุงล่าสุด',
        ];
    }

    /**
     * helper: ตั้งค่าพื้นฐานตอนสร้างจาก SSO (ถ้าจำเป็น)
     */
    public function initDefaultsForSso(): void
    {
        if ($this->isNewRecord) {
            // ★★ ปรับตามที่ขอ: ให้ position = 1 ครั้งแรก ★★
            if ($this->position === null) {
                $this->position = 1;
            }
            if (empty($this->authKey)) {
                $this->authKey = Yii::$app->security->generateRandomString(32);
            }
        }
    }

    /* ============================================================
     *         IdentityInterface สำหรับใช้กับ Yii::$app->user
     * ============================================================ */

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    // ปกติเราไม่ใช้ access token กับตารางนี้
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function getId()
    {
        return $this->uid;
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /* ============================================================
     *                     Relations / Helpers
     * ============================================================ */

    public function getOrgid()
    {
        $session = Yii::$app->session;
        $ty = $session['ty'] ?? null;
        if ($ty == 11) {
            return ArrayHelper::map(Organize::find()->all(),'org_id','org_name');
        } else {
            return ArrayHelper::map(
                Organize::find()->where(['org_id'=>$ty])->all(),
                'org_id',
                'org_name'
            );
        }
    }

    public function getPositions()
    {
        if (!Yii::$app->user->isGuest) {
            $Positions = Position::find()
                ->where(['positionid' => Yii::$app->user->identity->position])
                ->all();

            if (Yii::$app->user->identity->position != 1) {
                $Positions = Position::find()
                    ->where(['positionid'=>1])
                    ->orWhere(['positionid'=>2])
                    ->orderBy('positionid')
                    ->all();
            }
            if (Yii::$app->user->identity->position == 4) {
                $Positions = Position::find()->orderBy('positionid')->all();
            }
        } else {
            $Positions = Position::find()->orderBy('positionid')->all();
        }

        return ArrayHelper::map($Positions, 'positionid', function ($Position) {
            return $Position->positionname;
        });
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
