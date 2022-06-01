<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class TagsByCategoryDataTag extends CourseBaseDataTag implements DataTag
{
    /**
     * 获取所有标签
     *
     * 可传入的参数：
     *
     *   count : 标签数
     *
     * @param  array $arguments 参数
     * @return array 标签
     */

    public function getData(array $arguments)
    {
        $this->checkCount($arguments);
        $allTags = $this->getTagService()->findAllTags(0, 500);
        $category = $this->getCategoryService()->getCategoryByCode($arguments['categoryCode']);

        $conditions = array(
            'status' => 'published',
            'categoryId' => $category['id']
        );
        $courses = $this->getCourseService()->searchCourses($conditions, 'latest', 0, $arguments['count']);
        $tags = array();
        foreach ($allTags as $perTag) {
            foreach ($courses as $course) {
                if (in_array($perTag['id'], $course['tags']) && !in_array($perTag, $tags)) {
                    $tags[] = $perTag;
                }
            }
        }
        return $tags;
    }

    protected function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }

}
