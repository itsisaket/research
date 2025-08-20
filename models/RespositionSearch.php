<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Resposition;

/**
 * RespositionSearch represents the model behind the search form of `app\models\Resposition`.
 */
class RespositionSearch extends Resposition
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['res_positionid'], 'integer'],
            [['res_positionname'], 'safe'],
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
        $query = Resposition::find();

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
            'res_positionid' => $this->res_positionid,
        ]);

        $query->andFilterWhere(['like', 'res_positionname', $this->res_positionname]);

        return $dataProvider;
    }
}
