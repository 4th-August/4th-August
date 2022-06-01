<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class CourseSizeDataTag extends CourseBaseDataTag implements DataTag
{
    /**
     * 获取系统课程总数
     *     @return string 课程总数
     */

    public function getData(array $arguments)
    {
        $lessons = $this->getCourseService()->getCourseLessons($arguments['courseId']);
        $length = 0;
        foreach($lessons as $lesson) {
            if(!is_null($lesson['length'])){
                $length+=$lesson['length'];
            }
        }

        $minute = (int)($length/60);
        return $minute;


    }

}
