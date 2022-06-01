<?php

namespace Topxia\Service\Group\Dao;

interface ThreadLikeDao
{
    public function getThreadByUserIdAndThreadId($userId, $threadId);

    public function addThreadLike($collectThread);

    public function deleteThreadLikeByUserIdAndThreadId($userId,$threadId);

    public function getThreadLike($id);

    public function searchThreadLikesCount($conditions);

    public function searchThreadLikes($conditions,$orderBy,$start,$limit);

}