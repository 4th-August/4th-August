<?php
namespace Custom\WebBundle\Controller\Part;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\WebBundle\Controller\Part\CourseController as BaseController;

class CourseController extends BaseController
{
    public function headerAction($course, $member)
    {


        if (($course['discountId'] > 0)&&($this->isPluginInstalled("Discount"))){
            $course['discountObj'] = $this->getDiscountService()->getDiscount($course['discountId']);
        }

        $hasFavorited = $this->getCourseService()->hasFavoritedCourse($course['id']);


        $user = $this->getCurrentUser();
        $userVipStatus = $courseVip = null;
        if ($this->isPluginInstalled('Vip') && $this->setting('vip.enabled')) {
            $courseVip = $course['vipLevelId'] > 0 ? $this->getLevelService()->getLevel($course['vipLevelId']) : null;
            if ($courseVip) {
                $userVipStatus = $this->getVipService()->checkUserInMemberLevel($user['id'], $courseVip['id']);
            }
        }

        $nextLearnLesson = $member ? $this->getCourseService()->getUserNextLearnLesson($user['id'], $course['id']) : null;
        $learnProgress = $member ? $this->calculateUserLearnProgress($course, $member) : null;

        $previewLesson = $this->getCourseService()->searchLessons(array('courseId' => $course['id'], 'type' => 'video', 'free' => 1), array('seq', 'ASC'), 0, 1);

        $breadcrumbs = $this->getCategoryService()->findCategoryBreadcrumbs($course['categoryId']);


//        if($course['type']=='travel'){
//            return $this->render('TopxiaWebBundle:Course:Part/travel-header.html.twig', array(
//                'course' => $course,
//                'member' => $member,
//                'hasFavorited' => $hasFavorited,
//                'courseVip' => $courseVip,
//                'userVipStatus' => $userVipStatus,
//                'nextLearnLesson' => $nextLearnLesson,
//                'learnProgress' => $learnProgress,
//                'previewLesson' => empty($previewLesson) ? null : $previewLesson[0],
//                'breadcrumbs' => $breadcrumbs
//            ));
//        }

//        var_dump()

        return $this->render('TopxiaWebBundle:Course:Part/normal-header.html.twig', array(
            'course' => $course,
            'member' => $member,
            'hasFavorited' => $hasFavorited,
            'courseVip' => $courseVip,
            'userVipStatus' => $userVipStatus,
            'nextLearnLesson' => $nextLearnLesson,
            'learnProgress' => $learnProgress,
            'previewLesson' => empty($previewLesson) ? null : $previewLesson[0],
            'breadcrumbs' => $breadcrumbs
        ));
    }

    public function studentsAction($course)
    {
        $course = $this->getCourse($course);
        $members = $this->getCourseService()->findCourseStudents($course['id'], 0, 15);
        $students = $this->getUserService()->findUsersByIds(ArrayToolkit::column($members, 'userId'));

        return $this->render('TopxiaWebBundle:Course:Part/normal-layout-students.html.twig', array(
            'course' => $course,
            'students' => $students,
            'members' => $members
        ));
    }
}

