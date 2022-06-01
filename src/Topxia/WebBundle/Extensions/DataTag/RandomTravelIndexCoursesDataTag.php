<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class RandomTravelIndexCoursesDataTag extends CourseBaseDataTag implements DataTag
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
        
        $courses = $this->getCourseService()->searchRandomCourses('travel',$arguments['count']);
//
//        $courses_new=array();
//        //处理about字段
//        foreach ($courses as $course){
//            $course['about']= mb_substr(preg_replace("/<([a-zA-Z]+)[^>]*>/","<\\1>",$course['about']), 0, 50, 'utf-8')."...";
//            $courses_new[]=$course;
//        }
//

//        var_dump($courses);exit;

        return $this->getCourseTeachersAndCategories($courses);
    }
}
