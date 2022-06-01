<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class ThreadLikeCountDataTag extends CourseBaseDataTag implements DataTag
{
    /**
     * 获取精选课程问答列表
     *
     * 可传入的参数：
     *   courseId 必需 课程ID
     *   count 必需 课程话题数量，取值不能超过100
     *
     * @param  array $arguments 参数
     * @return array 课程话题
     */

    public function getData(array $arguments)
    {
        $conditions['threadId'] = $arguments['threadId'];

        $count = $this->getThreadService()->searchThreadLikesCount($conditions);
        return $count;

    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Group.ThreadService');
    }

}