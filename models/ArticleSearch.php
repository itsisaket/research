<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class ArticleSearch extends Article
{
    public $researcher_name; // ⭐ ค้นหาชื่อ-สกุลนักวิจัย

    public function rules()
    {
        return [
            [['article_id', 'org_id', 'publication_type'], 'integer'],
            [['article_th', 'researcher_name'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Article::find()->alias('a')
            ->joinWith(['user u', 'publi p']); // user=Account, publi=Publication

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['article_id' => SORT_DESC],
                'attributes' => [
                    'article_id',
                    'article_th',
                    'publication_type',
                    // sort เพิ่มตามชื่อผู้ใช้ (ถ้าต้องการ)
                    'researcher_name' => [
                        'asc' => ['u.uname' => SORT_ASC, 'u.luname' => SORT_ASC],
                        'desc'=> ['u.uname' => SORT_DESC, 'u.luname' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // exact filter
        $query->andFilterWhere([
            'a.publication_type' => $this->publication_type,
        ]);

        // like filters
        $query->andFilterWhere(['like', 'a.article_th', $this->article_th]);

        // ค้นหานักวิจัยด้วยชื่อ/นามสกุล (Account.uname / Account.luname)
        if (!empty($this->researcher_name)) {
            $query->andFilterWhere(['or',
                ['like', 'u.uname', $this->researcher_name],
                ['like', 'u.luname', $this->researcher_name],
            ]);
        }

        return $dataProvider;
    }
}
