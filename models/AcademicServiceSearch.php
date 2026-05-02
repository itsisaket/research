<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class AcademicServiceSearch extends AcademicService
{
    /** @var string Quick search keyword */
    public $q;
    /** @var string ช่วงวันที่ปฏิบัติงาน (จาก) */
    public $date_from;
    /** @var string ช่วงวันที่ปฏิบัติงาน (ถึง) */
    public $date_to;

    public function rules()
    {
        return [
            [['service_id', 'type_id', 'org_id', 'status'], 'integer'],
            [['username', 'title', 'location', 'service_date', 'q'], 'safe'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = AcademicService::find()->alias('s')
            ->joinWith(['serviceType st'], false)  // ประเภท
            ->joinWith(['user u'], false);         // เจ้าของ (ถ้า index ใช้ ownerFullname ผ่าน relation)

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['service_date' => SORT_DESC, 'service_id' => SORT_DESC],
                'attributes' => [
                    'service_id',
                    'title',
                    'hours',
                    'service_date' => [
                        'asc'  => ['s.service_date' => SORT_ASC],
                        'desc' => ['s.service_date' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        // sort เพิ่มสำหรับคอลัมน์ relation (ไว้ใช้ตอนเพิ่มคอลัมน์ type_name ใน grid)
        $dataProvider->sort->attributes['type_name'] = [
            'asc'  => ['st.type_name' => SORT_ASC],
            'desc' => ['st.type_name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        /** =========================
         * จำกัดข้อมูลตามสิทธิ์
         * ========================= */
        $me = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity : null;
        $pos = $me ? (int)($me->position ?? 0) : 0;

        // guest: เห็นเฉพาะรายการสถานะปกติ (หรือถ้าต้องการปิดทั้งหมด ให้ return query->andWhere('0=1'))
        if (Yii::$app->user->isGuest) {
            $query->andWhere(['s.status' => 1]);
        } else {
            // admin: เห็นทั้งหมด
            if ($pos === 4) {
                // no limit
            }
            // researcher: แนะนำให้เห็นเฉพาะของตัวเอง (ปลอดภัย)
            elseif ($pos === 1) {
                $query->andWhere(['s.username' => (string)$me->username]);
            }
            // อื่นๆ: เห็นเฉพาะหน่วยงานตัวเอง
            else {
                $ty = Yii::$app->session->get('ty');
                $orgId = $ty ?: ($me->org_id ?? null);
                if (!empty($orgId)) {
                    $query->andWhere(['s.org_id' => (int)$orgId]);
                } else {
                    // ถ้าไม่รู้ org เลย ให้เห็นเฉพาะของตัวเองเป็น fallback
                    $query->andWhere(['s.username' => (string)$me->username]);
                }
            }
        }

        /** =========================
         * Quick search (OR LIKE หลาย field)
         * ========================= */
        $q = trim((string)$this->q);
        if ($q !== '') {
            $isNumeric = ctype_digit($q);

            $or = ['or',
                ['like', 's.title',     $q],
                ['like', 's.location',  $q],
                ['like', 's.work_desc', $q],
                ['like', 's.note',      $q],
                ['like', 'u.uname',     $q],
                ['like', 'u.luname',    $q],
            ];
            if ($isNumeric) {
                $or[] = ['s.service_id' => (int)$q];
            }
            $query->andWhere($or);
        }

        /** =========================
         * Filters จากฟอร์มค้นหา (Advanced)
         * ========================= */
        $query->andFilterWhere([
            's.service_id' => $this->service_id,
            's.type_id'    => $this->type_id,
            's.org_id'     => $this->org_id,
            's.status'     => $this->status,
        ]);

        $query->andFilterWhere(['like', 's.title', $this->title])
              ->andFilterWhere(['like', 's.location', $this->location]);

        // username: ถ้าใช้ Select2 จะเป็น exact match ดีกว่า like
        if (!empty($this->username)) {
            $query->andFilterWhere(['s.username' => $this->username]);
        }

        // service_date รองรับทั้ง yyyy-mm-dd และ d/m/Y หรือ d-m-Y
        if (!empty($this->service_date)) {
            $d = trim((string)$this->service_date);

            // แปลง d/m/Y หรือ d-m-Y -> Y-m-d
            if (preg_match('/^\d{1,2}[\/-]\d{1,2}[\/-]\d{4}$/', $d)) {
                $d = str_replace('/', '-', $d);
                $ts = strtotime($d);
                if ($ts) {
                    $d = date('Y-m-d', $ts);
                }
            }

            $query->andFilterWhere(['s.service_date' => $d]);
        }

        // ===== ช่วงวันที่ปฏิบัติงาน =====
        if (!empty($this->date_from)) {
            $query->andWhere(['>=', 's.service_date', $this->date_from]);
        }
        if (!empty($this->date_to)) {
            $query->andWhere(['<=', 's.service_date', $this->date_to]);
        }

        return $dataProvider;
    }
}
