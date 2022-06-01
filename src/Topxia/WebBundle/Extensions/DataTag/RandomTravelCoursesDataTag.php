<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class RandomTravelCoursesDataTag extends CourseBaseDataTag implements DataTag
{

    /**
     * 随机输出课程列表
     *
     * 可传入的参数：
     *   categoryId 可选 分类ID
     *   categoryCode 可选　分类CODE
     *   type 可选　课程类型：live直播, normal 普通
     *   count    必需 课程数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 课程列表
     */
    public function getData(array $arguments)
    {	

        $conditions= array(
            'type'           => 'travel',
            'parentId'  =>0,
            'status'    => 'published',
        );
        $categoryArray             = $this->getCategoryService()->getCategoryByCode('YXW401');
        $childrenIds               = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds               = array_merge($childrenIds, array($categoryArray['id']));
        $conditions['categoryIds'] = $categoryIds;
        $ACourses = $this->getCourseService()->searchCourses(
            $conditions,
            "rand",
            0,
            1
        );

        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW402');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $conditions['categoryIds'] = $categoryIds;
        $BCourses = $this->getCourseService()->searchCourses(
            $conditions,
            "rand",
            0,
            1
        );


        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW403');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $conditions['categoryIds'] = $categoryIds;
        $CCourses = $this->getCourseService()->searchCourses(
            $conditions,
            "rand",
            0,
            1
        );
        $courses=array_merge($ACourses,$BCourses,$CCourses);


        return $this->getCourseTeachersAndCategories($courses);
    }
}
