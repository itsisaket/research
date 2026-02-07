<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Utilization;

/**
 * UtilizationSearch represents the model behind the search form of `app\models\Utilization`.
 */

class UtilizationSearch extends Utilization
{
    public function rules()
    {
        return [
            [['utilization_id', 'org_id', 'utilization_type', 'sub_district', 'district', 'province'], 'integer'],
            [['project_name', 'username'], 'safe'], // ⭐ สำคัญ
            [['utilization_add', 'utilization_date', 'utilization_detail', 'utilization_refer'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Utilization::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['utilization_id' => SORT_DESC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // ===== filter แบบ exact =====
        $query->andFilterWhere([
            'utilization_id'   => $this->utilization_id,
            'org_id'           => $this->org_id,
            'utilization_type' => $this->utilization_type,
            'utilization_date' => $this->utilization_date,
        ]);

        // ===== filter แบบค้นหา =====
        $query->andFilterWhere(['like', 'project_name', $this->project_name])
              ->andFilterWhere(['like', 'username', $this->username])
              ->andFilterWhere(['like', 'utilization_add', $this->utilization_add])
              ->andFilterWhere(['like', 'utilization_detail', $this->utilization_detail])
              ->andFilterWhere(['like', 'utilization_refer', $this->utilization_refer]);

        return $dataProvider;
    }
}
