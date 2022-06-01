<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class ClassroomSizeDataTag extends ClassroomsDataTag implements DataTag
{
    /**
     * 获取系统课程总数
     * @return string 课程总数
     */

    public function getData(array $arguments)
    {
        $courses = $this->getClassroomService()->findCoursesByClassroomId($arguments['classroomId']);
        $length = 0;
        foreach ($courses as $key => $course) {
            $lessons = $this->getCourseService()->getCourseLessons($course['id']);
            foreach ($lessons as $lesson) {
                if (!is_null($lesson['length'])) {
                    $length += $lesson['length'];
                }
            }
        }

        $minute = (int)($length / 60);
        return $minute;
    }

}
