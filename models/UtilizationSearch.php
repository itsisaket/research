<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Utilization;

/**
 * UtilizationSearch
 * --------------------------------------------------------------
 *  - $q       : Quick search (OR LIKE หลาย field)
 *  - field เดิม: project_name, username, org_id, utilization_type, ...
 */
class UtilizationSearch extends Utilization
{
    /** @var string Quick search keyword */
    public $q;

    public function rules()
    {
        return [
            [['utilization_id', 'org_id', 'utilization_type',
              'sub_district', 'district', 'province'], 'integer'],
            [['project_name', 'username', 'q'], 'safe'],
            [['utilization_add', 'utilization_date', 'utilization_detail',
              'utilization_refer'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Utilization::find()->alias('u');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['utilization_id' => SORT_DESC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // ===== Quick search =====
        $q = trim((string)$this->q);
        if ($q !== '') {
            $isNumeric = ctype_digit($q);

            // join ไป Account เพื่อค้นด้วยชื่อ
            $query->leftJoin('tb_user a', 'a.username = u.username');

            $or = ['or',
                ['like', 'u.project_name',       $q],
                ['like', 'u.utilization_add',    $q],
                ['like', 'u.utilization_detail', $q],
                ['like', 'u.utilization_refer',  $q],
                ['like', 'a.uname',              $q],
                ['like', 'a.luname',             $q],
            ];
            if ($isNumeric) {
                $or[] = ['u.utilization_id' => (int)$q];
            }
            $query->andWhere($or);
        }

        // ===== exact filters (Advanced) =====
        $query->andFilterWhere([
            'u.utilization_id'   => $this->utilization_id,
            'u.org_id'           => $this->org_id,
            'u.utilization_type' => $this->utilization_type,
            'u.province'         => $this->province,
            'u.utilization_date' => $this->utilization_date,
        ]);

        // ===== text filters (Advanced) =====
        $query->andFilterWhere(['like', 'u.project_name', $this->project_name]);

        if (!empty($this->username)) {
            // username เก่าเคยเป็น textInput → ทั้งกรณีค้นชื่อหรือเลือก
            $query->andFilterWhere(['or',
                ['u.username' => $this->username],
                ['like', 'u.username', $this->username],
            ]);
        }

        $query->andFilterWhere(['like', 'u.utilization_add',    $this->utilization_add])
              ->andFilterWhere(['like', 'u.utilization_detail', $this->utilization_detail])
              ->andFilterWhere(['like', 'u.utilization_refer',  $this->utilization_refer]);

        return $dataProvider;
    }
}
