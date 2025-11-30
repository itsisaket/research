<?php

namespace app\models;

use Yii;
use app\models\Organize;
use app\models\Position;
use yii\helpers\ArrayHelper;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

class Account extends \yii\db\ActiveRecord implements IdentityInterface
{
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

    // ฟอร์มปกติ ใช้ตอนกรอกเองในระบบ
    $sc['default'] = [
        'username',
        'password',
        'prefix',
        'uname',
        'luname',
        'org_id',
        'dept_code',
        'email',
        'tel',
        'position',
        'password_reset_token',
        'authKey',
        'dayup',
    ];

    // ซิงก์จาก SSO/JWT → ใช้เฉพาะ field ที่ map จาก HRM
    $sc['ssoSync'] = [
        'username',
        'prefix',
        'uname',
        'luname',
        'org_id',
        'dept_code',
        'email',
        'tel',
        'position',
        'password_reset_token',
        'authKey',
        'dayup',
    ];

    return $sc;
}


public function rules()
{
    return [
        // 1) required หลัก
        [['username'], 'required', 'on' => ['default', 'ssoSync']],

        [['prefix', 'uname', 'luname', 'org_id','dept_code', 'email', 'tel'], 'required', 'on' => ['default']],

        ['password', 'filter', 'filter' => function($v){ return $v === '' ? null : $v; }],

        // 👉 ใช้ integer ให้ตรงกับ DB
        [['prefix', 'org_id', 'position', 'dept_code'], 'integer'],

        // 👉 กรณี SSO ไม่ส่ง dept_code มาเลย → ให้ default เป็น 0
        ['dept_code', 'default', 'value' => 0],

        [['dayup'], 'safe'],
        [['username', 'password', 'password_reset_token', 'authKey', 'email'], 'string', 'max' => 50],
        [['uname', 'luname'], 'string', 'max' => 100],
        [['username'], 'match','pattern' => '/^[a-zA-Z0-9]*$/i','message' => 'Invalid characters in username.'],

        [['username'], 'unique'],

        // email unique เฉพาะ default (กัน SSO ซ้ำ)
        ['email', 'unique', 'filter' => ['not in', 'email', [null, '']], 'on' => ['default']],
        ['email', 'email', 'skipOnEmpty' => true],

        ['tel', 'string', 'max' => 20],
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
            'org_id' => 'หน่วยงานสังกัด/คณะ',
            'dept_code' => 'หน่วยงานย่อย/สาขาวิชา',
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
            // ตั้ง active/position ครั้งแรก
            if ($this->position === null) {
                $this->position = 1;
            }
            // authKey
            if (empty($this->authKey)) {
                $this->authKey = Yii::$app->security->generateRandomString(32);
            }
        }

        // กันค่า SSO ว่าง ๆ
        if ($this->email === null) {
            $this->email = '';
        }
        if ($this->tel === null) {
            $this->tel = '';
        }
        if ($this->prefix === null) {
            $this->prefix = 0;
        }
        if ($this->org_id === null) {
            $this->org_id = 0;
        }
    }

    /* ============================================================
     *         IdentityInterface สำหรับใช้กับ Yii::$app->user
     * ============================================================ */

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

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
