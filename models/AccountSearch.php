<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Account;


/**
 * AccountSearch represents the model behind the search form of `app\models\Account`.
 */
class AccountSearch extends Account
{
    public $q;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'prefix', 'org_id', 'tel'], 'integer'],
            [['username', 'password', 'password_reset_token', 'authKey', 'uname', 'luname', 'email','q', 'dayup'], 'safe'],
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
        $query = Account::find();
  
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
  
        $this->load($params);
  
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }


        $query->orFilterWhere(['like', 'uname', $this->q])
        ->orFilterWhere(['like', 'luname', $this->q]);
  
        return $dataProvider;
    }
/*
    public function search($params)
    {
        $query = Account::find();

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
            'uid' => $this->uid,
            'prefix' => $this->prefix,
            'org_id' => $this->org_id,
            'tel' => $this->tel,
            'academic' => $this->academic,
            'dayup' => $this->dayup,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'authKey', $this->authKey])
            ->andFilterWhere(['like', 'uname', $this->uname])
            ->andFilterWhere(['like', 'luname', $this->luname])
            ->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }
    */
}
