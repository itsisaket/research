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
            [['projectID', 'org_id', 'projectYearsubmit', 'fundingAgencyID'], 'integer'],
            [['projectNameTH', 'username'], 'safe'],
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

        // ===== exact filters (dropdown) =====
        $query->andFilterWhere([
            'projectYearsubmit' => $this->projectYearsubmit, // ปีเสนอ
            'fundingAgencyID'   => $this->fundingAgencyID,   // แหล่งทุน (ตรงกับ _search)
        ]);

        // ===== text filters =====
        $query->andFilterWhere(['like', 'projectNameTH', $this->projectNameTH]); // ชื่อโครงการ
        $query->andFilterWhere(['like', 'username', $this->username]);          // หัวหน้าโครงการ

        return $dataProvider;
    }
}
