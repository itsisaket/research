<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Article;

/**
 * ArticleSearch represents the model behind the search form of `app\models\Article`.
 */
class ArticleSearch extends Article
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['article_id', 'uid', 'org_id', 'publication_type'], 'integer'],
            [['article_th', 'article_eng', 'article_publish', 'journal', 'refer'], 'safe'],
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
        $query = Article::find();

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
            'article_id' => $this->article_id,
            'uid' => $this->uid,
            'org_id' => $this->org_id,
            'publication_type' => $this->publication_type,
            'article_publish' => $this->article_publish,
        ]);

        $query->andFilterWhere(['like', 'article_th', $this->article_th])
            ->andFilterWhere(['like', 'article_eng', $this->article_eng])
            ->andFilterWhere(['like', 'journal', $this->journal])
            ->andFilterWhere(['like', 'refer', $this->refer]);

        return $dataProvider;
    }
}
