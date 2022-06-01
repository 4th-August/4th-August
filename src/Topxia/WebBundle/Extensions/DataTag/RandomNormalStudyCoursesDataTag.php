<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class RandomNormalStudyCoursesDataTag extends CourseBaseDataTag implements DataTag
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
//        $this->checkCount($arguments);

//        $conditions = array('status' => 'published', 'type'=>'normal' ,'parentId' => 0);

//        if (!empty($arguments['categoryId'])) {
//            $conditions['categoryId'] = $arguments['categoryId'];
//        }
//
//        if (!empty($arguments['categoryCode'])) {
//            $category = $this->getCategoryService()->getCategoryByCode($arguments['categoryCode']);
//            $conditions['categoryId'] = empty($category) ? -1 : $category['id'];
//        }

//        if (!empty($arguments['type'])) {
//            $conditions['type'] = $arguments['type'];
//        }

//        by rand() limit 20
        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW001');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $ACourses = $this->getCourseService()->searchNormalRandomCourses($categoryIds,1);
        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW003');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $BCourses = $this->getCourseService()->searchNormalRandomCourses($categoryIds,1);

        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW002');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $CCourses = $this->getCourseService()->searchNormalRandomCourses($categoryIds,1);

        $courses=array_merge($ACourses,$BCourses,$CCourses);


//        var_dump($courses);exit;
//
//        $courses_new=array();
//        //处理about字段
//        foreach ($courses as $course){
//            $course['about']= mb_substr(preg_replace("/<([a-zA-Z]+)[^>]*>/","<\\1>",$course['about']), 0, 50, 'utf-8')."...";
//            $courses_new[]=$course;
//        }
//

//        var_dump($this->getCourseTeachersAndCategories($courses));exit;

        return $this->getCourseTeachersAndCategories($courses);
    }
}
