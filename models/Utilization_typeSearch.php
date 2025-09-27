<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Utilization_type;

/**
 * Utilization_typeSearch represents the model behind the search form of `app\models\Utilization_type`.
 */
class Utilization_typeSearch extends Utilization_type
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['utilization_type', 'utilization_type_name'], 'integer'],
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
        $query = Utilization_type::find();

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
            'utilization_type' => $this->utilization_type,
            'utilization_type_name' => $this->utilization_type_name,
        ]);

        return $dataProvider;
    }
}
