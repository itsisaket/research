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
    public $researcher_name;
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['article_id', 'org_id', 'publication_type'], 'integer'],
            [['article_th', 'article_eng', 'article_publish', 'journal', 'refer', 'username', 'researcher_name'], 'safe'],
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
        $query = Article::find()->alias('a')
            ->joinWith(['user u']); // ✅ join ไป Account ผ่าน getUser()

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['article_id' => SORT_DESC],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // exact filters
        $query->andFilterWhere([
            'a.article_id' => $this->article_id,
            'a.org_id' => $this->org_id,
            'a.publication_type' => $this->publication_type,
        ]);

        // like filters
        $query->andFilterWhere(['like', 'a.article_th', $this->article_th]);

        // ✅ นักวิจัย (ค้นด้วยชื่อ/นามสกุล)
        if (!empty($this->researcher_name)) {
            $query->andFilterWhere(['or',
                ['like', 'u.uname', $this->researcher_name],
                ['like', 'u.luname', $this->researcher_name],
            ]);
        }

        return $dataProvider;
    }

}
