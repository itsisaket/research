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
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['utilization_id', 'uid', 'org_id', 'utilization_type', 'sub_district', 'district', 'province'], 'integer'],
            [['utilization_add', 'utilization_date', 'utilization_detail', 'utilization_refer'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Utilization::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'utilization_id' => $this->utilization_id,
            'project_name' => $this->project_name,
            'uid' => $this->uid,
            'org_id' => $this->org_id,
            'utilization_type' => $this->utilization_type,
            'sub_district' => $this->sub_district,
            'district' => $this->district,
            'province' => $this->province,
            'utilization_date' => $this->utilization_date,
        ]);

        $query->andFilterWhere(['like', 'utilization_add', $this->utilization_add])
            ->andFilterWhere(['like', 'utilization_detail', $this->utilization_detail])
            ->andFilterWhere(['like', 'utilization_refer', $this->utilization_refer]);

        return $dataProvider;
    }
}
