<?php
namespace Custom\WebBundle\Controller;

use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Topxia\Service\Util\EdusohoLiveClient;
use Symfony\Component\HttpFoundation\Request;
use Topxia\WebBundle\Controller\CourseController as CourseBaseController;

class CourseController extends CourseBaseController
{

    public function studentshowAction(Request $request, $id)
    {
        list($course, $member) = $this->buildCourseLayoutData($request, $id);

        if ($course['parentId']) {
            $classroom = $this->getClassroomService()->findClassroomByCourseId($course['id']);

            if (!$this->getClassroomService()->canLookClassroom($classroom['classroomId'])) {
                return $this->createMessageResponse('info', '非常抱歉，您无权限访问该班级，如有需要请联系客服', '', 3, $this->generateUrl('homepage'));
            }
        }

        if (empty($member)) {
            $user = $this->getCurrentUser();
            $member = $this->getCourseService()->becomeStudentByClassroomJoined($id, $user->id);

            if (isset($member["id"])) {
                $course['studentNum']++;
            }
        }

        $this->getCourseService()->hitCourse($id);

        $items = $this->getCourseService()->getCourseItems($course['id']);
        $courseAbout = $course['about'];

        $courseAbout = strip_tags($courseAbout, '');

        $courseAbout = preg_replace("/ /", "", $courseAbout);
        $courseAbout = substr($courseAbout, 0, 100);

        return $this->render("TopxiaWebBundle:Course:studentsshow.html.twig", array(
            'course' => $course,
            'member' => $member,
            'items' => $items,
            'courseAbout' => $courseAbout
        ));
    }

    public function relatedCoursesBlockAction($course)
    {
        $courses = $this->getCourseService()->findNormalCoursesByAnyTagIdsAndStatus($course['tags'], 'published', array('rating desc,recommendedTime desc ,createdTime desc', ''), 0, 5);
        $tags = $this->getTagService()->findTagsByIds($course['tags']);
        return $this->render("TopxiaWebBundle:Course:related-courses-block.html.twig", array(
            'courses'       => $courses,
            'currentCourse' => $course,
            'tags'          => $tags
        ));
    }
}
