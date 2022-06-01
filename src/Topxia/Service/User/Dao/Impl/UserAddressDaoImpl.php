<?php

namespace Topxia\Service\User\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\User\Dao\UserAddressDao;

class UserAddressDaoImpl extends BaseDao implements UserAddressDao
{
    protected $table = 'user_address';

    public function getAddress($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function getAddressByUserId($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE userId = ? ORDER BY isD DESC LIMIT 20";
        return $this->getConnection()->fetchAll($sql, array($userId)) ? : array();
    }


	public function addAddress($address)
	{
        if($address['isD']){
            $userId=$address['userId'];
            $sql = "update {$this->table} SET isD = 0 WHERE isD = 1 AND userId = $userId";
            $this->getConnection()->exec($sql);
        }
        $affected = $this->getConnection()->insert($this->table, $address);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert profile error.');
        }
        return $this->getAddress($this->getConnection()->lastInsertId());
	}

	public function updateAddress($id, $address)
	{
	    $userId=$address['userId'];
        if($address['isD']){
            $sql = "update {$this->table} SET isD = 0 WHERE isD = 1 AND userId = $userId";
            $this->getConnection()->exec($sql);
        }

        $this->getConnection()->update($this->table, $address, array('id' => $id));
        return $this->getAddress($id);
	}

    public function deleteAddress($id)
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
    }

    public function dropFieldData($fieldName)
    {   
        $fieldNames = array(
            'intField1',
            'intField2',
            'intField3',
            'intField4',
            'intField5',
            'dateField1',
            'dateField2',
            'dateField3',
            'dateField4',
            'dateField5',
            'floatField1',
            'floatField2',
            'floatField3',
            'floatField4',
            'floatField5',
            'textField1',
            'textField2',
            'textField3',
            'textField4',
            'textField5',
            'textField6',
            'textField7',
            'textField8',
            'textField9',
            'textField10',
            'varcharField1',
            'varcharField2',
            'varcharField3',
            'varcharField4',
            'varcharField5',
            'varcharField6',
            'varcharField7',
            'varcharField8',
            'varcharField9',
            'varcharField10');
        if (!in_array($fieldName, $fieldNames)) {
            throw $this->createDaoException('fieldName error');
        }

        $sql="UPDATE {$this->table} set {$fieldName} =null ";
        return $this->getConnection()->exec($sql);
    }

    public function searchProfiles($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->createProfileQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);
        return $builder->execute()->fetchAll() ? : array();
    }

    public function searchProfileCount($conditions)
    {
        $builder = $this->createProfileQueryBuilder($conditions)
            ->select('COUNT(id)');
        return $builder->execute()->fetchColumn(0);
    }

    private function createProfileQueryBuilder($conditions)
    {
        $conditions = array_filter($conditions,function($v){
            if($v === 0){
                return true;
            }
                
            if(empty($v)){
                return false;
            }
            return true;
        });

        if (isset($conditions['mobile'])) {
            $conditions['mobile'] = "{$conditions['mobile']}%";
        }

        if (isset($conditions['qq'])) {
            $conditions['qq'] = "{$conditions['qq']}%";
        }

        if (isset($conditions['keywordType']) && isset($conditions['keyword']) && $conditions['keywordType'] == 'truename') {
            $conditions['truename'] = "%{$conditions['keyword']}%";
        }

        if (isset($conditions['keywordType']) && isset($conditions['keyword']) && $conditions['keywordType'] == 'idcard') {
            $conditions['idcard'] = "%{$conditions['keyword']}%";
        } 

        return  $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, 'user_profile')
            ->andWhere('mobile LIKE :mobile')
            ->andWhere('truename LIKE :truename')
            ->andWhere('idcard LIKE :idcard')
            ->andWhere('qq LIKE :qq');
    }
}