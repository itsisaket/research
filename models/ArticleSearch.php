<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use app\models\Article;

/**
 * ArticleSearch
 * --------------------------------------------------------------
 *  - $q              : Quick search (OR LIKE หลาย field)
 *  - $date_from/$date_to : ช่วงวันที่เผยแพร่
 *  - field เดิม      : article_th, publication_type, researcher_name, username, org_id
 *
 *  ⚠ หมายเหตุ:
 *    ฟิลด์ article_publish ในตารางเก็บเป็น string หลาย format ปนกัน:
 *      - DD-MM-YYYY (ตามค่า default ของ _form.php)
 *      - YYYY-MM-DD (ISO)
 *      - DD/MM/YYYY
 *    การค้นหา/เรียง จึงต้องใช้ STR_TO_DATE + COALESCE แปลงเป็น DATE ก่อนเปรียบเทียบ
 */
class ArticleSearch extends Article
{
    public $researcher_name;

    /** @var string Quick search keyword */
    public $q;
    /** @var string ช่วงวันที่เผยแพร่ (จาก) — Y-m-d */
    public $date_from;
    /** @var string ช่วงวันที่เผยแพร่ (ถึง) — Y-m-d */
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

    /**
     * SQL expression ที่แปลง article_publish (string หลาย format) → DATE
     * ใช้ใน WHERE และ ORDER BY
     */
    protected function publishDateExpr(): string
    {
        return "COALESCE("
            . "STR_TO_DATE(a.article_publish, '%d-%m-%Y'), "
            . "STR_TO_DATE(a.article_publish, '%Y-%m-%d'), "
            . "STR_TO_DATE(a.article_publish, '%d/%m/%Y')"
            . ")";
    }

    public function search($params)
    {
        $query = Article::find()->alias('a')
            ->joinWith(['user u']); // join ไป Account ผ่าน getUser()

        $dExpr = $this->publishDateExpr();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['article_publish' => SORT_DESC, 'article_id' => SORT_DESC],
                'attributes' => [
                    'article_id',
                    'article_th',
                    // ✅ ใช้ Expression เป็น value (ไม่ใช่ key) เพื่อให้ Yii ใส่เข้า ORDER BY ตรงๆ
                    'article_publish' => [
                        'asc'  => [new Expression("$dExpr ASC")],
                        'desc' => [new Expression("$dExpr DESC")],
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

        // ===== ช่วงวันที่เผยแพร่ — ใช้ STR_TO_DATE multi-format =====
        if (!empty($this->date_from)) {
            $query->andWhere(
                new Expression("$dExpr >= :df_article", [':df_article' => $this->date_from])
            );
        }
        if (!empty($this->date_to)) {
            $query->andWhere(
                new Expression("$dExpr <= :dt_article", [':dt_article' => $this->date_to])
            );
        }

        return $dataProvider;
    }
}
