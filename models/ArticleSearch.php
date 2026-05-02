<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Article;

/**
 * ArticleSearch
 * --------------------------------------------------------------
 *  - $q              : Quick search (OR LIKE หลาย field)
 *  - field เดิม      : article_th, publication_type, researcher_name, username, org_id
 */
class ArticleSearch extends Article
{
    public $researcher_name;

    /** @var string Quick search keyword */
    public $q;
    /** @var string ช่วงวันที่เผยแพร่ (จาก) */
    public $date_from;
    /** @var string ช่วงวันที่เผยแพร่ (ถึง) */
    public $date_to;

    public function rules()
    {
        return [
            [['article_id', 'org_id', 'publication_type', 'branch'], 'integer'],
            [['article_th', 'article_eng', 'article_publish', 'journal',
              'refer', 'username', 'researcher_name', 'q'], 'safe'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Article::find()->alias('a')
            ->joinWith(['user u']); // join ไป Account ผ่าน getUser()

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['article_publish' => SORT_DESC, 'article_id' => SORT_DESC],
                'attributes' => [
                    'article_id',
                    'article_th',
                    'article_publish' => [
                        'asc'  => ['a.article_publish' => SORT_ASC],
                        'desc' => ['a.article_publish' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // ===== Quick search =====
        $q = trim((string)$this->q);
        if ($q !== '') {
            $isNumeric = ctype_digit($q);

            $or = ['or',
                ['like', 'a.article_th',  $q],
                ['like', 'a.article_eng', $q],
                ['like', 'a.journal',     $q],
                ['like', 'a.refer',       $q],
                ['like', 'u.uname',       $q],
                ['like', 'u.luname',      $q],
            ];
            if ($isNumeric) {
                $or[] = ['a.article_id' => (int)$q];
            }
            $query->andWhere($or);
        }

        // ===== exact filters (Advanced) =====
        $query->andFilterWhere([
            'a.article_id'        => $this->article_id,
            'a.org_id'            => $this->org_id,
            'a.publication_type'  => $this->publication_type,
            'a.branch'            => $this->branch,
        ]);

        // ===== text filters (Advanced) =====
        $query->andFilterWhere(['like', 'a.article_th', $this->article_th]);

        // นักวิจัย (ค้นด้วยชื่อ/นามสกุล)
        if (!empty($this->researcher_name)) {
            $query->andFilterWhere(['or',
                ['like', 'u.uname',  $this->researcher_name],
                ['like', 'u.luname', $this->researcher_name],
            ]);
        }

        // username (Select2 → exact match)
        if (!empty($this->username)) {
            $query->andFilterWhere(['a.username' => $this->username]);
        }

        // ===== ช่วงวันที่เผยแพร่ =====
        if (!empty($this->date_from)) {
            $query->andWhere(['>=', 'a.article_publish', $this->date_from]);
        }
        if (!empty($this->date_to)) {
            $query->andWhere(['<=', 'a.article_publish', $this->date_to]);
        }

        return $dataProvider;
    }
}
