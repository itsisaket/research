<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Publication;

/**
 * PublicationSearch represents the model behind the search form of `app\models\Publication`.
 */
class PublicationSearch extends Publication
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['publication_type'], 'integer'],
            [['publication_name', 'publication_detail'], 'safe'],
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
        $query = Publication::find();

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
            'publication_type' => $this->publication_type,
        ]);

        $query->andFilterWhere(['like', 'publication_name', $this->publication_name])
            ->andFilterWhere(['like', 'publication_detail', $this->publication_detail]);

        return $dataProvider;
    }
}
