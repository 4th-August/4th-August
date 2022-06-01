<?php

namespace Topxia\WebBundle\Controller;

use Topxia\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    public function indexAction()
    {

        if($this->isMobile()){
            return $this->forward('TopxiaWebBundle:Default:mIndex');
        }


        $conditions = array('status' => 'published', 'parentId' => 0, 'recommended' => 1);
        $courses    = $this->getCourseService()->searchCourses($conditions, 'recommendedSeq', 0, 12);
        $orderBy    = 'recommendedSeq';

        if (empty($courses)) {
            $orderBy = 'latest';
            unset($conditions['recommended']);
            $courses = $this->getCourseService()->searchCourses($conditions, 'latest', 0, 12);
        }

        $coinSetting = $this->getSettingService()->get('coin', array());

        if (isset($coinSetting['cash_rate'])) {
            $cashRate = $coinSetting['cash_rate'];
        } else {
            $cashRate = 1;
        }

        $courseSetting = $this->getSettingService()->get('course', array());

        if (!empty($courseSetting['live_course_enabled']) && $courseSetting['live_course_enabled']) {
            $recentLiveCourses = $this->getRecentLiveCourses();
        } else {
            $recentLiveCourses = array();
        }

        $categories = $this->getCategoryService()->findGroupRootCategories('course');

        $blocks = $this->getBlockService()->getContentsByCodes(array('home_top_banner'));
        $user   = $this->getCurrentUser();

        if (!empty($user['id'])) {
            $this->getBatchNotificationService()->checkoutBatchNotification($user['id']);
        }
    //添加班级
        $conditionsc = array();
        $conditionsc['status']   = 'published';
        $conditionsc['showable'] = 1;
        $orderByc = !isset($conditionsc['orderBy']) ? 'createdTime' : $conditionsc['orderBy'];
        $classrooms = $this->getClassroomService()->searchClassrooms(
            $conditionsc,
            array('createdTime', 'desc'),
           null,null
        );

        return $this->render('TopxiaWebBundle:Default:index.html.twig', array(
            'courses'           => $courses,
            'categories'        => $categories,
            'blocks'            => $blocks,
            'recentLiveCourses' => $recentLiveCourses,
            'consultDisplay'    => true,
            'cashRate'          => $cashRate,
            'orderBy'           => $orderBy,
            'classrooms'        => $classrooms
        ));
    }

    protected function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备

        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }

        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息

        if (isset($_SERVER['HTTP_VIA'])) {
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }

        //判断手机发送的客户端标志,兼容性有待提高

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp',
                'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu',
                'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi',
                'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字

            if (preg_match("/(".implode('|', $clientkeywords).")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }

        //协议法，因为有可能不准确，放到最后判断

        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备

            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }

        return false;
    }

    public function mIndexAction()
    {
        $conditions = array('status' => 'published', 'parentId' => 0, 'recommended' => 1);
        $courses    = $this->getCourseService()->searchCourses($conditions, 'recommendedSeq', 0, 12);
        $orderBy    = 'recommendedSeq';

        if (empty($courses)) {
            $orderBy = 'latest';
            unset($conditions['recommended']);
            $courses = $this->getCourseService()->searchCourses($conditions, 'latest', 0, 12);
        }

        $coinSetting = $this->getSettingService()->get('coin', array());

        if (isset($coinSetting['cash_rate'])) {
            $cashRate = $coinSetting['cash_rate'];
        } else {
            $cashRate = 1;
        }

        $courseSetting = $this->getSettingService()->get('course', array());

        if (!empty($courseSetting['live_course_enabled']) && $courseSetting['live_course_enabled']) {
            $recentLiveCourses = $this->getRecentLiveCourses();
        } else {
            $recentLiveCourses = array();
        }

        $categories = $this->getCategoryService()->findGroupRootCategories('course');

        $blocks = $this->getBlockService()->getContentsByCodes(array('home_top_banner'));
        $user   = $this->getCurrentUser();

        if (!empty($user['id'])) {
            $this->getBatchNotificationService()->checkoutBatchNotification($user['id']);
        }
        //添加班级
        $conditionsc = array();
        $conditionsc['status']   = 'published';
        $conditionsc['showable'] = 1;
        $orderByc = !isset($conditionsc['orderBy']) ? 'createdTime' : $conditionsc['orderBy'];
        $classrooms = $this->getClassroomService()->searchClassrooms(
            $conditionsc,
            array('createdTime', 'desc'),
            null,null
        );

        //读取横幅
        $block = $this->getBlockService()->getBlockByCode('yingxiang:home_top_banner');
        //热门推荐

        $topNormalCourses = $this->getCourseService()->searchCourses(
            array(
                'type'           => 'normal',
                'recommended'    => 1,
                'parentId'       => '0',
                'status'         => 'published'
            ),
            'recommendedSeq',
            0,
            4
        );

        $topTravelCourses = $this->getCourseService()->searchCourses(
            array(
                'type'           => 'travel',
                'recommended'    => 1,
                'parentId'       => '0',
                'status'         => 'published'
            ),
            'recommendedSeq',
            0,
            4
        );

        $topLiveCourses = $this->getCourseService()->searchCourses(
            array(
                'type'           => 'live',
                'recommended'    => null,
                'parentId'       => '0',
                'status'         => 'published'
            ),
            'recommendedSeq',
            0,
            4
        );


        $topWeikeCourses = $this->getCourseByCategory('YXW001');

        $topZTCourses = $this->getCourseByCategory('YXW003');

        $topBJCourses = $this->getCourseByCategory('YXW002');

        $topGKCourses = $this->getCourseByCategory('YXW005');

        $topJQCourses = $this->getCourseByCategory('YXW403');

        $topGNCourses = $this->getCourseByCategory('YXW401');

        $topGJCourses = $this->getCourseByCategory('YXW402');

        $topZBCourses = $this->getCourseByCategory('YXW404');

        $topJLCourses = $this->getCourseByCategory('YXW0007');

        $topDKCourses = $this->getCourseByCategory('YXW0008');

        $topSYCourses = $this->getCourseByCategory('YXW0009');

        $topHXCourses = $this->getCourseByCategory('YXW00010');

        $topSZNCourses = $this->getCourseByCategory('YXW0091');

        $topSZYCourses = $this->getCourseByCategory('YXW0092');

        $topSZHCourses = $this->getCourseByCategory('YXW0093');



        return $this->render('TopxiaWebBundle:Default:m-index.html.twig', array(
            'courses'           => $courses,
            'categories'        => $categories,
            'blocks'            => $blocks,
            'recentLiveCourses' => $recentLiveCourses,
            'consultDisplay'    => true,
            'cashRate'          => $cashRate,
            'orderBy'           => $orderBy,
            'classrooms'        => $classrooms,
            'posters'           => $block['data']['posters'],
            'topNormalCourses'  => $topNormalCourses,
            'topTravelCourses'  => $topTravelCourses,
            'topLiveCourses'    => $topLiveCourses,
            'topWeikeCourses'   => $topWeikeCourses,
            'topZTCourses'      => $topZTCourses,
            'topBJCourses'      => $topBJCourses,
            'topGKCourses'      => $topGKCourses,
            'topJQCourses'      => $topJQCourses,
            'topGNCourses'      => $topGNCourses,
            'topGJCourses'      => $topGJCourses,
            'topZBCourses'      => $topZBCourses,
            'topJLCourses'      => $topJLCourses,
            'topDKCourses'      => $topDKCourses,
            'topSYCourses'      => $topSYCourses,
            'topHXCourses'      => $topHXCourses,
            'topSZNCourses'      => $topSZNCourses,
            'topSZYCourses'      => $topSZYCourses,
            'topSZHCourses'      => $topSZHCourses,
            'NATree'   =>$this->categoryTree('YXW001'),
            'NBTree'   =>$this->categoryTree('YXW002'),
            'NCTree'   =>$this->categoryTree('YXW003'),
            'NDTree'   =>$this->categoryTree('YXW005'),
            'TATree'   =>$this->categoryTree('YXW401'),
            'TBTree'   =>$this->categoryTree('YXW402'),
            'TCTree'   =>$this->categoryTree('YXW403'),
            'TDTree'   =>$this->categoryTree('YXW404'),
            'LATree'   =>$this->categoryTree('YXW0007'),
            'LBTree'   =>$this->categoryTree('YXW0008'),
            'LCTree'   =>$this->categoryTree('YXW0009'),
            'LDTree'   =>$this->categoryTree('YXW00010'),
            'SATree'   =>$this->categoryTree('YXW0091'),
            'SBTree'   =>$this->categoryTree('YXW0092'),
            'SCTree'   =>$this->categoryTree('YXW0093'),
        ));
    }

    private function categoryTree($code){
        $category = $this->getCategoryService()->getCategoryByCode($code);
        $categorys = $this->getCategoryService()->findAllCategoriesByParentId($category['id']);
        $tree=array();
        foreach ($categorys as $vv){
            $temp = $this->getCategoryService()->findAllCategoriesByParentId($vv['id']);
            foreach ($temp as $vvv){
                $tempArray['name']=$vvv['name'];
                $tempArray['code']=$vvv['code'];
                $tree[$vv['name']][]=$tempArray;
            }
        }
        return $tree;
    }

    private function getCourseByCategory($categoryId){
        $categoryArray = $this->getCategoryService()->getCategoryByCode($categoryId);
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));

        $Courses = $this->getCourseService()->searchCourses(
            array(
                'categoryIds'           => $categoryIds,
                'recommended'    => 1,
                'parentId'       => '0',
                'status'         => 'published'
            ),
            'recommendedSeq',
            0,
            4
        );
        return $Courses;
    }



    public function userlearningAction()
    {
        $user = $this->getCurrentUser();

        $courses = $this->getCourseService()->findUserLearnCourses($user->id, 0, 1);

        if (!empty($courses)) {
            foreach ($courses as $course) {
                $member = $this->getCourseService()->getCourseMember($course['id'], $user->id);

                $teachers = $this->getUserService()->findUsersByIds($course['teacherIds']);
            }

            $nextLearnLesson = $this->getCourseService()->getUserNextLearnLesson($user->id, $course['id']);

            $progress = $this->calculateUserLearnProgress($course, $member);
        } else {
            $course          = array();
            $nextLearnLesson = array();
            $progress        = array();
            $teachers        = array();
        }

        return $this->render('TopxiaWebBundle:Default:user-learning.html.twig', array(
            'user'            => $user,
            'course'          => $course,
            'nextLearnLesson' => $nextLearnLesson,
            'progress'        => $progress,
            'teachers'        => $teachers
        ));
    }

    protected function getRecentLiveCourses()
    {
        $recenntLessonsCondition = array(
            'status'             => 'published',
            'endTimeGreaterThan' => time()
        );

        $recentlessons = $this->getCourseService()->searchLessons(
            $recenntLessonsCondition,
            array('startTime', 'ASC'),
            0,
            20
        );

        $courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($recentlessons, 'courseId'));

        $liveCourses = array();

        foreach ($recentlessons as $lesson) {
            $course = $courses[$lesson['courseId']];

            if ($course['status'] != 'published') {
                continue;
            }

            if ($course['parentId'] != 0) {
                continue;
            }

            $course['lesson']   = $lesson;
            $course['teachers'] = $this->getUserService()->findUsersByIds($course['teacherIds']);

            if (count($liveCourses) >= 8) {
                break;
            }

            $liveCourses[] = $course;
        }

        return $liveCourses;
    }

    public function promotedTeacherBlockAction()
    {
        $teacher = $this->getUserService()->findLatestPromotedTeacher(0, 1);

        if ($teacher) {
            $teacher = $teacher[0];
            $teacher = array_merge(
                $teacher,
                $this->getUserService()->getUserProfile($teacher['id'])
            );
        }

        if (isset($teacher['locked']) && $teacher['locked'] !== '0') {
            $teacher = null;
        }

        return $this->render('TopxiaWebBundle:Default:promoted-teacher-block.html.twig', array(
            'teacher' => $teacher
        ));
    }

    public function latestReviewsBlockAction($number)
    {
        $reviews = $this->getReviewService()->searchReviews(array('private' => 0), 'latest', 0, $number);
        $users   = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));
        $courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($reviews, 'courseId'));
        return $this->render('TopxiaWebBundle:Default:latest-reviews-block.html.twig', array(
            'reviews' => $reviews,
            'users'   => $users,
            'courses' => $courses
        ));
    }

    public function topNavigationAction($siteNav = null, $isMobile = false)
    {
        $navigations = $this->getNavigationService()->getOpenedNavigationsTreeByType('top');

        return $this->render('TopxiaWebBundle:Default:top-navigation.html.twig', array(
            'navigations' => $navigations,
            'siteNav'     => $siteNav,
            'isMobile'    => $isMobile
        ));
    }

    public function footNavigationAction()
    {
        $navigations = $this->getNavigationService()->findNavigationsByType('foot', 0, 100);

        return $this->render('TopxiaWebBundle:Default:foot-navigation.html.twig', array(
            'navigations' => $navigations
        ));
    }

    public function customerServiceAction()
    {
        $customerServiceSetting = $this->getSettingService()->get('customerService', array());

        return $this->render('TopxiaWebBundle:Default:customer-service-online.html.twig', array(
            'customerServiceSetting' => $customerServiceSetting
        ));
    }

    public function jumpAction(Request $request)
    {
        $courseId = intval($request->query->get('id'));

        if ($this->getCourseService()->isCourseTeacher($courseId, $this->getCurrentUser()->id)) {
            $url = $this->generateUrl('live_course_manage_replay', array('id' => $courseId));
        } else {
            $url = $this->generateUrl('course_show', array('id' => $courseId));
        }

        $jumpScript = "<script type=\"text/javascript\"> if (top.location !== self.location) {top.location = \"{$url}\";}</script>";
        return new Response($jumpScript);
    }

    public function coursesCategoryAction(Request $request)
    {
        //首页课程切换

        $conditions             = $request->query->all();
        $conditions['status']   = 'published';
        $conditions['parentId'] = 0;
        $categoryId             = isset($conditions['categoryId']) ? $conditions['categoryId'] : 0;

//        if (isset($conditions['config'])) {
//            $config = $conditions['config'];
//            unset($conditions['config']);
//        } else {
//            $config = array();
//            unset($conditions['config']);
//        }
//
//
//        if (!empty($conditions['categoryId'])) {
//            $conditions['categoryId'] = intval($conditions['categoryId']);
//        } else {
//            unset($conditions['categoryId']);
//        }
//
//        $orderBy = $conditions['orderBy'];
//
//        if ($orderBy == 'recommendedSeq') {
//            $conditions['recommended'] = 1;
//        }
//
//        unset($conditions['orderBy']);
        $config = $this->getThemeService()->getCurrentThemeConfig();
        $config = $config['confirmConfig']['blocks']['left'];

        foreach ($config as $template) {
            if ($template['code'] == "course-grid-with-condition-index") {
                $config = $template;
            }
        }

        $config['orderBy']    = 'recommendedSeq';
        $config['categoryId'] = $categoryId;

//        $courses = $this->getCourseService()->searchRandomCourses('normal',6);


        return $this->render('TopxiaWebBundle:Default:course-grid-with-refresh-index.html.twig', array(
            'config' => $config,
//            'excludeCategoryId'=>$excludeCategoryId
        ));
    }

    public function coursesClassroomAction(Request $request)
    {
        $conditions             = $request->query->all();
        $conditions['status']   = 'published';
        $conditions['parentId_GT'] = 0;
        $categoryId             = isset($conditions['categoryId']) ? $conditions['categoryId'] : 0;

        if (isset($conditions['config'])) {
            $config = $conditions['config'];
            unset($conditions['config']);
        } else {
            $config = array();
            unset($conditions['config']);
        }

        //var_dump($conditions);

        if (!empty($conditions['categoryId'])) {
            $conditions['categoryId'] = intval($conditions['categoryId']);
        } else {
            unset($conditions['categoryId']);
        }

        $orderBy = $conditions['orderBy'];

        if ($orderBy == 'recommendedSeq') {
            $conditions['recommended'] = 1;
        }
        //var_dump($conditions);
        unset($conditions['orderBy']);
        $config               = $this->getThemeService()->getCurrentThemeConfig();
        $config               = $config['confirmConfig']['blocks']['left'][0];
        $config['orderBy']    = $orderBy;
        $config['categoryId'] = $categoryId;
        return $this->render('TopxiaWebBundle:Default:course-grid-with-classroom.html.twig', array(
            'config' => $config,

        ));
    }
    protected function calculateUserLearnProgress($course, $member)
    {
        if ($course['lessonNum'] == 0) {
            return array('percent' => '0%', 'number' => 0, 'total' => 0);
        }

        $percent = intval($member['learnedNum'] / $course['lessonNum'] * 100).'%';

        return array(
            'percent' => $percent,
            'number'  => $member['learnedNum'],
            'total'   => $course['lessonNum']
        );
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }

    protected function getNavigationService()
    {
        return $this->getServiceKernel()->createService('Content.NavigationService');
    }

    protected function getBlockService()
    {
        return $this->getServiceKernel()->createService('Content.BlockService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    protected function getReviewService()
    {
        return $this->getServiceKernel()->createService('Course.ReviewService');
    }

    protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    protected function getAppService()
    {
        return $this->getServiceKernel()->createService('CloudPlatform.AppService');
    }

    protected function getClassroomService()
    {
        return $this->getServiceKernel()->createService('Classroom:Classroom.ClassroomService');
    }

    protected function getBatchNotificationService()
    {
        return $this->getServiceKernel()->createService('User.BatchNotificationService');
    }

    protected function getThemeService()
    {
        return $this->getServiceKernel()->createService('Theme.ThemeService');
    }

    private function getBlacklistService()
    {
        return $this->getServiceKernel()->createService('User.BlacklistService');
    }
}