<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Researchpro;

/**
 * ResearchproSearch
 * --------------------------------------------------------------
 *  - $q       : Quick search (OR LIKE หลาย field)
 *  - field เดิม: projectNameTH, username, projectYearsubmit, fundingAgencyID,
 *                researchTypeID, jobStatusID, org_id
 */
class ResearchproSearch extends Researchpro
{
    /** @var string Quick search keyword */
    public $q;
    /** @var string ช่วงวันที่ (จาก) — รูปแบบ Y-m-d */
    public $date_from;
    /** @var string ช่วงวันที่ (ถึง) */
    public $date_to;

    public function rules()
    {
        return [
            [['projectID', 'org_id', 'projectYearsubmit', 'fundingAgencyID',
              'researchTypeID', 'jobStatusID'], 'integer'],
            [['projectNameTH', 'username', 'q'], 'safe'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Researchpro::find()->alias('r');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['projectYearsubmit' => SORT_DESC, 'projectID' => SORT_DESC],
                'attributes' => [
                    'projectID',
                    'projectNameTH',
                    'projectYearsubmit',
                    'budgets',
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
            // ถ้าเป็นตัวเลขล้วน → ค้นหา projectID ตรงด้วย
            $isNumeric = ctype_digit($q);

            // join ไป Account เพื่อค้นด้วยชื่อ/นามสกุลได้
            $query->leftJoin('tb_user u', 'u.username = r.username');

            $or = ['or',
                ['like', 'r.projectNameTH', $q],
                ['like', 'r.projectNameEN', $q],
                ['like', 'r.researchArea',  $q],
                ['like', 'r.documentid',    $q],
                ['like', 'u.uname',         $q],
                ['like', 'u.luname',        $q],
            ];
            if ($isNumeric) {
                $or[] = ['r.projectID' => (int)$q];
                $or[] = ['r.projectYearsubmit' => (int)$q];
            }
            $query->andWhere($or);
        }

        // ===== exact filters (Advanced) =====
        $query->andFilterWhere([
            'r.projectYearsubmit' => $this->projectYearsubmit,
            'r.fundingAgencyID'   => $this->fundingAgencyID,
            'r.researchTypeID'    => $this->researchTypeID,
            'r.jobStatusID'       => $this->jobStatusID,
            'r.org_id'            => $this->org_id,
        ]);

        // ===== ช่วงวันที่เริ่มโครงการ =====
        if (!empty($this->date_from)) {
            $query->andWhere(['>=', 'r.projectStartDate', $this->date_from]);
        }
        if (!empty($this->date_to)) {
            $query->andWhere(['<=', 'r.projectStartDate', $this->date_to]);
        }

        // ===== text filters (Advanced) =====
        $query->andFilterWhere(['like', 'r.projectNameTH', $this->projectNameTH]);
        if (!empty($this->username)) {
            // ใช้ exact match เพราะ Select2 ส่งค่า username มาตรง
            $query->andFilterWhere(['r.username' => $this->username]);
        }

        return $dataProvider;
    }
}
