<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Academic;

/**
 * AcademicSearch represents the model behind the search form of `app\models\Academic`.
 */
class AcademicSearch extends Academic
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['academicid'], 'integer'],
            [['academicname', 'academiccode'], 'safe'],
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
        $query = Academic::find();

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
            'academicid' => $this->academicid,
        ]);

        $query->andFilterWhere(['like', 'academicname', $this->academicname])
            ->andFilterWhere(['like', 'academiccode', $this->academiccode]);

        return $dataProvider;
    }
}
