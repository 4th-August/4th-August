<?php

namespace Topxia\Service\Group\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Group\Dao\ThreadLikeDao;

class ThreadLikeDaoImpl extends BaseDao implements ThreadLikeDao
{
    protected $table = 'groups_thread_like';
    private $serializeFields = array(
        'tagIds' => 'json',
    );

    public function getThreadLike($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function getThreadByUserIdAndThreadId($userId, $threadId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE userId = ? AND threadId = ?";
        return $this->getConnection()->fetchAssoc($sql, array($userId, $threadId));
    }

    public function addThreadLike($collectThread)
    {

        $affected = $this->getConnection()->insert($this->table, $collectThread);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert threadLike error.');
        }
        return $this->getThreadLike($this->getConnection()->lastInsertId());
    }

    public function deleteThreadLikeByUserIdAndThreadId($userId,$threadId)
    {
        $sql = "DELETE FROM {$this->table} WHERE userId = ? AND threadId = ?";
        return $this->getConnection()->executeUpdate($sql, array($userId, $threadId));
    }

    public function findThreadLikesCountByUserIdAndThreadId($userId,$threadId)
    {
        $sql = "SELECT COUNT(id)  FROM {$this->table} WHERE userId = ? AND threadId = ?";
        return $this->getConnection()->fetchColumn($sql, array($userId, $threadId));
    }

    public function searchThreadLikesCount($conditions)
    {
        $builder = $this->_createThreadLikeSearchBuilder($conditions)
            ->select('count(threadId)');

        return $builder->execute()->fetchColumn(0);
    }

    protected function _createThreadLikeSearchBuilder($conditions)
    {
        $builder = $this->createDynamicQueryBuilder($conditions)
            ->from($this->table,$this->table)
            ->andWhere('userId = :userId')
            ->andWhere('threadId = :threadId');
        return $builder;
    }

    public function searchThreadLikes($conditions,$orderBy,$start,$limit)
    {
        $builder=$this->_createThreadLikeSearchBuilder($conditions)
            ->select('distinct threadId')
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->orderBy($orderBy[0],$orderBy[1]);

        return $builder->execute()->fetchAll() ? : array();
    }

}