<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Researchpro;

class ResearchproSearch extends Researchpro
{
    public function rules()
    {
        return [
            [['projectID', 'org_id', 'projectYearsubmit', 'budgets', 'fundingAgencyID', 'researchFundID', 'researchTypeID', 'jobStatusID', 'sub_district', 'district', 'province'], 'integer'],
            [['projectNameTH', 'projectNameEN', 'projectStartDate', 'projectEndDate', 'researchArea', 'username'], 'safe'], // ⭐ username เป็น safe เพื่อค้นหาได้
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Researchpro::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['projectYearsubmit' => SORT_DESC, 'projectID' => SORT_DESC],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // ===== exact filters (เลือกจาก dropdown) =====
        $query->andFilterWhere([
            'projectYearsubmit' => $this->projectYearsubmit, // ปีเสนอ
            'researchFundID'    => $this->researchFundID,    // แหล่งทุน
            'researchTypeID'    => $this->researchTypeID,    // ประเภทการวิจัย
        ]);

        // ===== text filters (พิมพ์ค้นหา) =====
        $query->andFilterWhere(['like', 'projectNameTH', $this->projectNameTH]) // ชื่อโครงการ
              ->andFilterWhere(['like', 'username', $this->username]);          // หัวหน้าโครงการ (username)

        return $dataProvider;
    }
}
