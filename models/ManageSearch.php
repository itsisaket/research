<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Manage;

/**
 * ManageSearch represents the model behind the search form of `app\models\Manage`.
 */
class ManageSearch extends Manage
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['manageid', 'pro_id', 'manage'], 'integer'],
            [['area', 'output', 'outcome', 'impact', 'dayup'], 'safe'],
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
        $query = Manage::find();

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
            'manageid' => $this->manageid,
            'pro_id' => $this->pro_id,
            'manage' => $this->manage,
            'dayup' => $this->dayup,
        ]);

        $query->andFilterWhere(['like', 'area', $this->area])
            ->andFilterWhere(['like', 'output', $this->output])
            ->andFilterWhere(['like', 'outcome', $this->outcome])
            ->andFilterWhere(['like', 'impact', $this->impact]);

        return $dataProvider;
    }
}
