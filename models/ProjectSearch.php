<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Project;

/**
 * ProjectSearch represents the model behind the search form of `app\models\Project`.
 */
class ProjectSearch extends Project
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pro_id', 'uid', 'pro_position', 'pro_capital', 'pro_type', 'pro_year', 'pro_budget', 'pro_status', 'sub_district', 'district', 'province'], 'integer'],
            [['pro_name', 'pro_keyword', 'pro_location', 'dayup'], 'safe'],
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
        $query = Project::find();

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
            'pro_id' => $this->pro_id,
            'uid' => $this->uid,
            'pro_position' => $this->pro_position,
            'pro_capital' => $this->pro_capital,
            'pro_type' => $this->pro_type,
            'pro_year' => $this->pro_year,
            'pro_budget' => $this->pro_budget,
            'pro_status' => $this->pro_status,
            'sub_district' => $this->sub_district,
            'district' => $this->district,
            'province' => $this->province,
            'dayup' => $this->dayup,
        ]);

        $query->andFilterWhere(['like', 'pro_name', $this->pro_name])
            ->andFilterWhere(['like', 'pro_keyword', $this->pro_keyword])
            ->andFilterWhere(['like', 'pro_location', $this->pro_location]);

        return $dataProvider;
    }
}
