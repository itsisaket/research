<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Researchpro;

/**
 * ResearchproSearch represents the model behind the search form of `app\models\Researchpro`.
 */
class ResearchproSearch extends Researchpro
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['projectID', 'username', 'org_id', 'projectYearsubmit', 'budgets', 'fundingAgencyID', 'researchFundID', 'researchTypeID', 'jobStatusID', 'sub_district', 'district', 'province'], 'integer'],
            [['projectNameTH', 'projectNameEN', 'projectStartDate', 'projectEndDate', 'researchArea'], 'safe'],
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
        $query = Researchpro::find();

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
            'projectID' => $this->projectID,
            'username' => $this->username,
            'org_id' => $this->org_id,
            'projectYearsubmit' => $this->projectYearsubmit,
            'budgets' => $this->budgets,
            'fundingAgencyID' => $this->fundingAgencyID,
            'researchFundID' => $this->researchFundID,
            'researchTypeID' => $this->researchTypeID,
            'projectStartDate' => $this->projectStartDate,
            'projectEndDate' => $this->projectEndDate,
            'jobStatusID' => $this->jobStatusID,
            'sub_district' => $this->sub_district,
            'district' => $this->district,
            'province' => $this->province,
        ]);

        $query->andFilterWhere(['like', 'projectNameTH', $this->projectNameTH])
            ->andFilterWhere(['like', 'projectNameEN', $this->projectNameEN])
            ->andFilterWhere(['like', 'researchArea', $this->researchArea]);

        return $dataProvider;
    }
}
