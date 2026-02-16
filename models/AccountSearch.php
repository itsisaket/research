<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class AccountSearch extends Account
{
    /** ค้นหารวม */
    public $q;

    public function rules()
    {
        return [
            // ตัวเลข
            [['uid', 'prefix', 'org_id', 'position', 'dept_code'], 'integer'],

            // string/safe
            [['username', 'uname', 'luname', 'email', 'tel', 'dayup', 'q'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Account::find()
            ->alias('a')
            ->with(['hasprefix', 'hasorg', 'hasposition']); // preload relations

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => [
                    'org_id'    => SORT_ASC,
                    'uname'  => SORT_ASC,
                ],
                'attributes' => [
                    'uid',
                    'username',
                    'uname',
                    'luname',
                    'org_id',
                    'dept_code',
                    'position',
                    'email',
                    'tel',
                    'dayup',
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // filter exact
        $query->andFilterWhere([
            'a.uid'       => $this->uid,
            'a.prefix'    => $this->prefix,
            'a.org_id'    => $this->org_id,
            'a.position'  => $this->position,
            'a.dept_code' => $this->dept_code,
        ]);

        // filter แยกราย field
        $query->andFilterWhere(['like', 'a.username', $this->username])
              ->andFilterWhere(['like', 'a.uname',    $this->uname])
              ->andFilterWhere(['like', 'a.luname',   $this->luname])
              ->andFilterWhere(['like', 'a.email',    $this->email])
              ->andFilterWhere(['like', 'a.tel',      $this->tel]);

        // filter รวม q
        if ($this->q) {
            $query->andFilterWhere([
                'or',
                ['like', 'a.username', $this->q],
                ['like', 'a.uname',    $this->q],
                ['like', 'a.luname',   $this->q],
                ['like', 'a.email',    $this->q],
                ['like', 'a.tel',      $this->q],
            ]);
        }

        return $dataProvider;
    }
}
