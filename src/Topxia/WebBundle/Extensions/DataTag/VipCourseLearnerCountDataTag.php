<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class VipCourseLearnerCountDataTag extends CourseBaseDataTag implements DataTag
{
    /**
     * 获取一个VIP等级
     *
     * @todo  要挪到Vip插件中去
     *
     * @param  array $arguments 参数
     * @return array vip等级
     */

    public function getData(array $arguments)
    {
        $conditions = array('level'=>$arguments['vipLevelId']);
        $count = $this->getVipService()->searchMembersCount($conditions);
        return $count;
    }


    protected function getVipService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.VipService');
    }
}
