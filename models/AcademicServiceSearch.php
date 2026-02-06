<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class AcademicServiceSearch extends AcademicService
{
    public function rules()
    {
        return [
            [['service_id', 'type_id', 'org_id', 'status'], 'integer'],
            [['username', 'title', 'location', 'service_date'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = AcademicService::find()->alias('s')
            ->joinWith(['serviceType st', 'user u']); // ใช้ใน index แสดงชื่อ/ประเภท

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['service_date' => SORT_DESC, 'service_id' => SORT_DESC],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        // sort เพิ่มสำหรับคอลัมน์ relation
        $dataProvider->sort->attributes['type_name'] = [
            'asc'  => ['st.type_name' => SORT_ASC],
            'desc' => ['st.type_name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            's.service_id' => $this->service_id,
            's.type_id'    => $this->type_id,
            's.org_id'     => $this->org_id,
            's.status'     => $this->status,
        ]);

        $query->andFilterWhere(['like', 's.username', $this->username])
              ->andFilterWhere(['like', 's.title', $this->title])
              ->andFilterWhere(['like', 's.location', $this->location]);

        // service_date: ถ้า UI ส่ง dd-mm-yyyy มาก็ยัง filter แบบ string ได้ (ง่าย/ไม่พัง)
        if (!empty($this->service_date)) {
            $query->andFilterWhere(['s.service_date' => $this->service_date]);
        }

        return $dataProvider;
    }
}
