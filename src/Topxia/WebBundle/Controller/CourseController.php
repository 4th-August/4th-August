<?php
namespace Topxia\WebBundle\Controller;

use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Topxia\Service\Util\EdusohoLiveClient;
use Symfony\Component\HttpFoundation\Request;

class CourseController extends CourseBaseController
{
    public function exploreAction(Request $request, $category)
    {
        $conditions    = $request->query->all();
        $categoryArray = array();
        $levels        = array();

        $conditions['code'] = $category;
        $categoryOrg=$category;

        if (!empty($conditions['code'])) {
            $categoryArray             = $this->getCategoryService()->getCategoryByCode($conditions['code']);
            $childrenIds               = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
            $categoryIds               = array_merge($childrenIds, array($categoryArray['id']));
            $conditions['categoryIds'] = $categoryIds;
        }

        unset($conditions['code']);

        if (!isset($conditions['fliter'])) {
            $conditions['fliter'] = array(
                'type'           => 'all',
                'price'          => 'all',
                'currentLevelId' => 'all'
            );
        }


        //处理商品列表的横幅
        $category=$categoryArray;
        $banner_img='';
        if(isset($category['name'])){
            $i=0;
            $categoryIndexArray=array('摄影装备','制作中心','服务中心','学习交流','摄影大咖','旅游直播','精彩回顾');
            while (!in_array($category['name'],$categoryIndexArray) ){
                $i++;
                $category = $this->getCategoryService()->getCategory($category['parentId']);
                if($i>5){
                    break;
                }
            }
            $categoryName='';
            $categoryIndex='';
            $categoryChild=array();
            if(in_array($category['name'],$categoryIndexArray)){
                $categoryName=$category['name'];
                $categoryChild = $this->getCategoryService()->findAllCategoriesByParentId($category['id']);
                $categoryIndex=$category['code'];
                switch ($category['name']){
                    case '摄影装备':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:product_a_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '制作中心':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:product_b_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '服务中心':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:product_c_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '学习交流':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:liva_a_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '摄影大咖':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:liva_b_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '旅游直播':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:liva_c_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '精彩回顾':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:liva_d_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    default:
                        break;
                }
            }
        }


        $fliter = $conditions['fliter'];

        if ($fliter['price'] == 'free') {
            $coinSetting = $this->getSettingService()->get("coin");
            $coinEnable  = isset($coinSetting["coin_enabled"]) && $coinSetting["coin_enabled"] == 1;
            $priceType   = "RMB";

            if ($coinEnable && !empty($coinSetting) && array_key_exists("price_type", $coinSetting)) {
                $priceType = $coinSetting["price_type"];
            }

            if ($priceType == 'RMB') {
                $conditions['price'] = '0.00';
            } else {
                $conditions['coinPrice'] = '0.00';
            }
        }

        if ($fliter['type'] == 'live') {
            $conditions['type'] = 'live';
        }elseif($fliter['type'] == 'product'){
            $conditions['type'] = 'product';
        }else{
            $conditions['type'] = 'normal';
        }

        if (isset($fliter['tag'])){
            $fliterTag = $fliter['tag'];
        }

        if ($this->isPluginInstalled('Vip')) {
            $levels = ArrayToolkit::index($this->getLevelService()->searchLevels(array('enabled' => 1), 0, 100), 'id');

            if ($fliter['currentLevelId'] != 'all') {
                $vipLevelIds               = ArrayToolkit::column($this->getLevelService()->findPrevEnabledLevels($fliter['currentLevelId']), 'id');
                $conditions['vipLevelIds'] = array_merge(array($fliter['currentLevelId']), $vipLevelIds);
            }
        }

        unset($conditions['fliter']);

        $courseSetting = $this->getSettingService()->get('course', array());

        if (!isset($courseSetting['explore_default_orderBy'])) {
            $courseSetting['explore_default_orderBy'] = 'latest';
        }

        $orderBy = $courseSetting['explore_default_orderBy'];
        $orderBy = empty($conditions['orderBy']) ? $orderBy : $conditions['orderBy'];
        unset($conditions['orderBy']);

        $conditions['recommended'] = ($orderBy == 'recommendedSeq') ? 1 : null;

        $conditions['parentId'] = 0;
        $conditions['status']   = 'published';
        if (isset($fliterTag)){
            $tag = $this->getTagService()->getTagByName($fliter['tag']);
            $conditions['tagId'] = $tag['id'];
        }
        $paginator              = new Paginator(
            $this->get('request'),
            $this->getCourseService()->searchCourseCount($conditions),
            21
        );

        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            $orderBy,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $courses = $this->getCourseTeachersAndCategories($courses);

        $group = $this->getCategoryService()->getGroupByCode('course');

        if (empty($group)) {
            $categories = array();
        } else {
            $categories = $this->getCategoryService()->getCategoryTree($group['id']);
        }

        if (!$categoryArray) {
            $categoryArrayDescription = array();
        } else {
            $categoryArrayDescription = $categoryArray['description'];
            $categoryArrayDescription = strip_tags($categoryArrayDescription, '');
            $categoryArrayDescription = preg_replace("/ /", "", $categoryArrayDescription);
            $categoryArrayDescription = substr($categoryArrayDescription, 0, 100);
        }

        if (!$categoryArray) {
            $categoryParent = '';
        } else {
            if (!$categoryArray['parentId']) {
                $categoryParent = '';
            } else {
                $categoryParent = $this->getCategoryService()->getCategory($categoryArray['parentId']);
            }
        }


        //处理商品

        foreach ($courses as $kk=>$course) {
            if($course['type']=='product'){
                $courses[$kk]['travelDetail']=json_decode($course['travelDetail'],true);
                //生成评论数
                $courses[$kk]['reviewCount']=$this->getReviewService()->getCourseReviewCount($course['id']);
            }
        }



        return $this->render('TopxiaWebBundle:Course:explore.html.twig', array(
            'courses'                  => $courses,
            'category'                 => $categoryOrg,
            'fliter'                   => $fliter,
            'orderBy'                  => $orderBy,
            'bannerImg'                => $banner_img,
            'paginator'                => $paginator,
            'categories'               => $categories,
            'consultDisplay'           => true,
            'path'                     => 'course_explore',
            'categoryArray'            => $categoryArray,
            'group'                    => $group,
            'categoryArrayDescription' => $categoryArrayDescription,
            'categoryParent'           => $categoryParent,
            'levels'                   => $levels,
        ));
    }

    public function travelExploreAction(Request $request, $category)
    {
        $conditions    = $request->query->all();
        $categoryArray = array();
        $levels        = array();

        $conditions['code'] = $category;
        $categoryCode=$category;
        $categoryChild=array();
        $banner_img='';
        $categoryName='';
        $categoryIndex='';
        if (!empty($conditions['code'])) {
            $categoryArray             = $this->getCategoryService()->getCategoryByCode($conditions['code']);
            $childrenIds               = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
            $categoryIds               = array_merge($childrenIds, array($categoryArray['id']));
            $conditions['categoryIds'] = $categoryIds;

            //查找父分类
//            $category             = $this->getCategoryService()->getCategoryByCode("YXW4011");
//            $category = $this->getCategoryService()->getCategory($categoryArray['parentId']);  var_dump($category);exit;
            $i=0;
            $category=$categoryArray;
            $categoryIndexArray=array('国内线路','国际线路','影视剧情','周边游拍');

            while (!in_array($category['name'],$categoryIndexArray) ){
                $i++;
                $category = $this->getCategoryService()->getCategory($category['parentId']);
                if($i>5){
                    break;
                }
            }
            if(in_array($category['name'],$categoryIndexArray)){
                $categoryName=$category['name'];
                $categoryChild = $this->getCategoryService()->findAllCategoriesByParentId($category['id']);
                $categoryIndex=$category['code'];
                switch ($category['name']){
                    case '国内线路':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:travel_a_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '国际线路':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:travel_b_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '影视剧情':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:travel_c_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    case '周边游拍':
                        $block = $this->getBlock()->getBlockByCode('yingxiang:travel_d_banner');
                        $banner_img=$block['data']['banner'][0]['src'];
                        break;
                    default:
                        break;
                }
            }
        }

        unset($conditions['code']);

        if (!isset($conditions['fliter'])) {
            $conditions['fliter'] = array(
                'type'           => 'travel',
                'price'          => 'all',
                'currentLevelId' => 'all'
            );
        }

        $fliter = $conditions['fliter'];


        if (isset($fliter['month'])){
            $conditions['month'] = $fliter['month'];
        }

        if ($fliter['price'] == 'free') {
            $coinSetting = $this->getSettingService()->get("coin");
            $coinEnable  = isset($coinSetting["coin_enabled"]) && $coinSetting["coin_enabled"] == 1;
            $priceType   = "RMB";

            if ($coinEnable && !empty($coinSetting) && array_key_exists("price_type", $coinSetting)) {
                $priceType = $coinSetting["price_type"];
            }

            if ($priceType == 'RMB') {
                $conditions['price'] = '0.00';
            } else {
                $conditions['coinPrice'] = '0.00';
            }
        }

//        if ($fliter['type'] == 'live') {
        $conditions['type'] = 'travel';
//        }

        if (isset($fliter['tag'])){
            $fliterTag = $fliter['tag'];
        }

        if (isset($fliter['range'])){
            if($fliter['range']=='all'){
                unset($fliter['range']);
            }else{
                $conditions['range'] = intval($fliter['range']);
            }
        }

        if (isset($fliter['tprice'])){
            if($fliter['tprice']=='all'){
                unset($fliter['tprice']);
            }else{
                $conditions['tprice'] = intval($fliter['tprice']);
            }
        }


//        if ($this->isPluginInstalled('Vip')) {
//            $levels = ArrayToolkit::index($this->getLevelService()->searchLevels(array('enabled' => 1), 0, 100), 'id');
//
//            if ($fliter['currentLevelId'] != 'all') {
//                $vipLevelIds               = ArrayToolkit::column($this->getLevelService()->findPrevEnabledLevels($fliter['currentLevelId']), 'id');
//                $conditions['vipLevelIds'] = array_merge(array($fliter['currentLevelId']), $vipLevelIds);
//            }
//        }

        unset($conditions['fliter']);

        $courseSetting = $this->getSettingService()->get('course', array());

        if (!isset($courseSetting['explore_default_orderBy'])) {
            $courseSetting['explore_default_orderBy'] = 'latest';
        }

        $orderBy = $courseSetting['explore_default_orderBy'];
        $orderBy = empty($conditions['orderBy']) ? $orderBy : $conditions['orderBy'];
        unset($conditions['orderBy']);

        $conditions['recommended'] = ($orderBy == 'recommendedSeq') ? 1 : null;

        $conditions['parentId'] = 0;
        $conditions['status']   = 'published';
        if (isset($fliterTag)){
            $tag = $this->getTagService()->getTagByName($fliter['tag']);
            $conditions['tagId'] = $tag['id'];
        }
        $paginator              = new Paginator(
            $this->get('request'),
            $this->getCourseService()->searchCourseCount($conditions),
            21
        );


        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            $orderBy,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );





        $topConditions= array(
            'type'           => 'travel',
            'recommended'    => null,
            'parentId'       => '0',
            'status'         => 'published'
        );

        $topCourses = $this->getCourseService()->searchCourses(
            $topConditions,
            'studentNum',
            0,
            10
        );


        $userIds = array();
        foreach ($topCourses as $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);

            if(count($userIds)==6)break;
        }

        $TopUsers = $this->getUserService()->findUsersByIds($userIds);
//        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);
//        foreach ($TopUsers as $key => $user) {
//            if ($user['id'] == $profiles[$user['id']]['id']) {
//                $TopUsers[$key]['profile'] = $profiles[$user['id']];
//            }
//        }




        $courses = $this->getCourseTeachersAndCategories($courses);

        $group = $this->getCategoryService()->getGroupByCode('course');

        if (empty($group)) {
            $categories = array();
        } else {
            $categories = $this->getCategoryService()->getCategoryTree($group['id']);
        }

        if (!$categoryArray) {
            $categoryArrayDescription = array();
        } else {
            $categoryArrayDescription = $categoryArray['description'];
            $categoryArrayDescription = strip_tags($categoryArrayDescription, '');
            $categoryArrayDescription = preg_replace("/ /", "", $categoryArrayDescription);
            $categoryArrayDescription = substr($categoryArrayDescription, 0, 100);
        }

        if (!$categoryArray) {
            $categoryParent = '';
        } else {
            if (!$categoryArray['parentId']) {
                $categoryParent = '';
            } else {
                $categoryParent = $this->getCategoryService()->getCategory($categoryArray['parentId']);
            }
        }
        $months=array();
        for($i = 1; $i <= 12; $i++){
//            $months[]=strtotime(date("Y-n", strtotime("+".$i." months", time())));
//            $months[]=strtotime(date("Y-n", strtotime("+".$i." months", time())));
//            $months[]=strtotime(date("Y",time())."-".$i."-1");
            $months[]=strtotime(date("Y",time())."-".$i."-1");
        }
//        var_dump($months);exit;

        //调用微信图片
        $block = $this->getBlock()->getBlockByCode('yingxiang:bottom_info');
        $img=array();
        if(isset($block['data']['weixin'][0]['src'])){
            $img['weixin']=$block['data']['weixin'][0]['src'];
        }
        if(isset($block['data']['apple'][0]['src'])){
            $img['apple']=$block['data']['apple'][0]['src'];
        }
        $categoryAll=true;

            foreach ( $categoryChild as $vv){
                if($vv['code'] == $categoryCode){
                    $categoryAll=false;
                }
            }



        //对每个课程的时间进行判断
        foreach ( $courses as $kk=>$vv){
            $courses[$kk]['travelStatus']=1;
//            if(time()<$vv['travelStartTime']){
//                $courses[$kk]['travelStatus']=1;
//            }
//            if(time()>=$vv['travelStartTime'] && time()< $vv['travelEndTime']){
//                $courses[$kk]['travelStatus']=2;
//            }
            //成团判断
            if($vv['travelPeople']<=$vv['studentNum']){
                $courses[$kk]['travelStatus']=2;
            }

            if(time()>=$vv['travelStartTime']){
                $courses[$kk]['travelStatus']=3;
            }
        }





        return $this->render('TopxiaWebBundle:Course:travelExplore.html.twig', array(
            'bannerImg'                => $banner_img,
            'categoryName'             => $categoryName,
            'categoryChilds'           => $categoryChild,
            'categoryIndex'            => $categoryIndex,
            'categoryAll'              => $categoryAll,
            'courses'                  => $courses,
            'img'                      => $img,
            'topTeacher'               => $TopUsers,
            'months'                   => $months,
            'topCourses'               => $topCourses,
            'category'                 => $categoryCode,
            'fliter'                   => $fliter,
            'orderBy'                  => $orderBy,
            'paginator'                => $paginator,
            'categories'               => $categories,
            'consultDisplay'           => true,
            'path'                     => 'course_explore',
            'categoryArray'            => $categoryArray,
            'group'                    => $group,
            'categoryArrayDescription' => $categoryArrayDescription,
            'categoryParent'           => $categoryParent,
            'levels'                   => $levels,
        ));
    }

    public function normalExploreAction(Request $request)
    {
//        $conditions    = $request->query->all();

//        //调用微信图片
//        $block = $this->getBlock()->getBlockByCode('yingxiang:bottom_info');
//        $img=array();
//        if(isset($block['data']['weixin'][0]['src'])){
//            $img['weixin']=$block['data']['weixin'][0]['src'];
//        }
//        if(isset($block['data']['apple'][0]['src'])){
//            $img['apple']=$block['data']['apple'][0]['src'];
//        }
        //读取横幅
//        $block = $this->getBlock()->getBlockByCode('yingxiang:normal_banner');
//        $banner_img=$block['data']['banner'][0]['src'];
        //读取四个分类下的课程

        $category = $this->getCategoryService()->getCategoryByCode('YXW001');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds = array_merge($childrenIds, array($category['id']));
        $ACourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'normal',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );
//        $block = $this->getBlock()->getBlockByCode('yingxiang:normal_B_info');

        //读取横幅
        $block = $this->getBlock()->getBlockByCode('yingxiang:normal_top_banner');


        $category = $this->getCategoryService()->getCategoryByCode('YXW002');
//        $categorys = $this->getCategoryService()->findAllCategoriesByParentId($category['id']);
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds = array_merge($childrenIds, array($category['id']));
//        $BTree=array();
//        foreach ($categorys as $vv){
//            $temp = $this->getCategoryService()->findAllCategoriesByParentId($vv['id']);
//            foreach ($temp as $vvv){
//                $tempArray['name']=$vvv['name'];
//                $tempArray['code']=$vvv['code'];
//                $BTree[$vv['name']][]=$tempArray;
//
//            }
//
//        }
        $BCourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'normal',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );
        $category = $this->getCategoryService()->getCategoryByCode('YXW003');
//        $categorys = $this->getCategoryService()->findAllCategoriesByParentId($category['id']);
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds = array_merge($childrenIds, array($category['id']));
//        $CTree=array();
//        foreach ($categorys as $vv){
//            $temp = $this->getCategoryService()->findAllCategoriesByParentId($vv['id']);
//            foreach ($temp as $vvv){
//                $tempArray['name']=$vvv['name'];
//                $tempArray['code']=$vvv['code'];
//                $CTree[$vv['name']][]=$tempArray;
//
//            }
//
//        }
        $CCourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'normal',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );
        $category = $this->getCategoryService()->getCategoryByCode('YXW005');
//        $categorys = $this->getCategoryService()->findAllCategoriesByParentId($category['id']);
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds = array_merge($childrenIds, array($category['id']));
//        $DTree=array();
//        foreach ($categorys as $vv){
//            $temp = $this->getCategoryService()->findAllCategoriesByParentId($vv['id']);
//            foreach ($temp as $vvv){
//                $tempArray['name']=$vvv['name'];
//                $tempArray['code']=$vvv['code'];
//                $DTree[$vv['name']][]=$tempArray;
//            }
//            if(count($temp)==0){
//                $DTree[$vv['name']][]=null;
//            }
//        }
        $DCourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'normal',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );



        $category = $this->getCategoryService()->getCategoryByCode('YXW001');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds = array_merge($childrenIds, array($category['id']));
        $category = $this->getCategoryService()->getCategoryByCode('YXW003');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds =  array_merge($categoryIds,array_merge($childrenIds, array($category['id'])));
        $category = $this->getCategoryService()->getCategoryByCode('YXW002');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds =  array_merge($categoryIds,array_merge($childrenIds, array($category['id'])));
        $category = $this->getCategoryService()->getCategoryByCode('YXW005');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($category['id']);
        $categoryIds =  array_merge($categoryIds,array_merge($childrenIds, array($category['id'])));



        //生成6个最热门课程
        $topCourses = $this->getCourseService()->searchCourses(
            array(
                'type'           => 'normal',
                'recommended'    => 1,
                'parentId'       => '0',
                'status'         => 'published',
                'categoryIds' => $categoryIds
            ),
            'recommendedSeq',
            0,
            6
        );
        $userIds = array();
        foreach ($topCourses as $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
        }
        $TopUsers   = $this->getUserService()->findUsersByIds($userIds);
        //生成周边游拍的教师
        $userIds = array();
        foreach ($DCourses as $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
        }
        $DUsers   = $this->getUserService()->findUsersByIds($userIds);

        //生成班级课程 3个

        $classrooms = $this->getClassroomService()->searchClassrooms(
            array(
                'status' => 'published',
                'private' => 0
            ),
            array('recommendedSeq', 'desc'),
            0,
            3
        );




        return $this->render('TopxiaWebBundle:Course:study.html.twig', array(
            'ACourses'=>$ACourses,
            'BCourses'=>$BCourses,
            'CCourses'=>$CCourses,
            'DCourses'=>$DCourses,
            'topCourses'=>$topCourses,
            'topUsers'=>$TopUsers,
            'DUsers'  =>$DUsers,
            'ATree'   =>$this->categoryTree('YXW001'),
            'BTree'   =>$this->categoryTree('YXW002'),
            'CTree'   =>$this->categoryTree('YXW003'),
            'DTree'   =>$this->categoryTree('YXW005'),
            'classrooms'  => $classrooms,
            'posters' => $block['data']['posters']
        ));
    }

    public function travelIndexAction(Request $request)
    {
//        $conditions    = $request->query->all();

        //调用微信图片
        $block = $this->getBlock()->getBlockByCode('yingxiang:bottom_info');
        $img=array();
        if(isset($block['data']['weixin'][0]['src'])){
            $img['weixin']=$block['data']['weixin'][0]['src'];
        }
        if(isset($block['data']['apple'][0]['src'])){
            $img['apple']=$block['data']['apple'][0]['src'];
        }
        //读取轮播图
        $block = $this->getBlock()->getBlockByCode('yingxiang:travel_top_banner');

        //读取四个分类下的课程


        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW401');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $ACourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'travel',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );
        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW402');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $BCourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'travel',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );
        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW403');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $CCourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'travel',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );
        $categoryArray = $this->getCategoryService()->getCategoryByCode('YXW404');
        $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
        $categoryIds = array_merge($childrenIds, array($categoryArray['id']));
        $DCourses = $this->getCourseService()->searchCourses(
            array(
                'type' => 'travel',
                'recommended' => null,
                'parentId' => '0',
                'status' => 'published',
                'categoryIds' => $categoryIds
            ),
            'latest',
            0,
            3
        );
        //生成6个最热门课程
        $topCourses = $this->getCourseService()->searchCourses(
            array(
                'type'           => 'travel',
                'recommended'    => 1,
                'parentId'       => '0',
                'status'         => 'published'
            ),
            'recommendedSeq',
            0,
            6
        );
        $userIds = array();
        foreach ($topCourses as $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
        }
        $TopUsers   = $this->getUserService()->findUsersByIds($userIds);
       //生成周边游拍的教师
        $userIds = array();
        foreach ($DCourses as $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
        }
        $DUsers   = $this->getUserService()->findUsersByIds($userIds);

        $category = $this->getCategoryService()->getCategoryByCode('YXW401');
        $categorys = $this->getCategoryService()->findAllCategoriesByParentId($category['id']);
        //构建分类树
        $ATree=array();
        foreach ($categorys as $vv){
            $temp = $this->getCategoryService()->findAllCategoriesByParentId($vv['id']);
            foreach ($temp as $vvv){
                $tempArray['name']=$vvv['name'];
                $tempArray['code']=$vvv['code'];
                $ATree[$vv['name']][]=$tempArray;
            }
        }

//        var_dump($block['data']['posters']);exit;


        return $this->render('TopxiaWebBundle:Course:travel.html.twig', array(
            'posters' => $block['data']['posters'],
            'ACourses'=>$ACourses,
            'BCourses'=>$BCourses,
            'CCourses'=>$CCourses,
            'DCourses'=>$DCourses,
            'topCourses'=>$topCourses,
            'topUsers'=>$TopUsers,
            'DUsers'  =>$DUsers,
            'ATree'   =>$this->categoryTree('YXW401'),
            'BTree'   =>$this->categoryTree('YXW402'),
            'CTree'   =>$this->categoryTree('YXW403'),
            'DTree'   =>$this->categoryTree('YXW404'),
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

    public function productIndexAction(Request $request)
    {
//        $conditions    = $request->query->all();

        //调用微信图片
        $block = $this->getBlock()->getBlockByCode('yingxiang:bottom_info');
        $img=array();
        if(isset($block['data']['weixin'][0]['src'])){
            $img['weixin']=$block['data']['weixin'][0]['src'];
        }
        if(isset($block['data']['apple'][0]['src'])){
            $img['apple']=$block['data']['apple'][0]['src'];
        }
        //读取横幅
        $block = $this->getBlock()->getBlockByCode('yingxiang:product_banner');
//        $banner_img=$block['data']['banner'][0]['src'];


        //读取商品
        $topCourses = $this->getCourseService()->searchCourses(
            array(
                'type'           => 'product',
                'status'         => 'published',
                'recommended'    => 1
            ),
            'recommendedSeq',
            0,
            9
        );


        $userIds = array();
        foreach ($topCourses as $kk=>$course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
            $topCourses[$kk]['travelDetail']=json_decode($course['travelDetail'],true);
            //生成评论数
            $topCourses[$kk]['reviewCount']=$this->getReviewService()->getCourseReviewCount($course['id']);
        }

        $TopUsers   = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('TopxiaWebBundle:Course:product.html.twig', array(
            'posters' => $block['data']['posters'],
            'Courses'=>$topCourses,
            'Users'=>$TopUsers
        ));
    }

    public function coursesCategoryAction(Request $request)
    {
        return $this->render('TopxiaWebBundle:Course:travel-refresh.html.twig');
    }

    public function normalCategoryAction(Request $request)
    {
        return $this->render('TopxiaWebBundle:Course:normal-refresh.html.twig');
    }


    public function joinAction(Request $request)
    {
        $conditions = array(
            'roles'=>'ROLE_TEACHER',
            'locked'=>0
        );

        $teachers = $this->getUserService()->searchUsers(
            $conditions,
            array('promotedTime', 'DESC'),
            0,
            12
        );
//        var_dump($teachers);exit;



        return $this->render('TopxiaWebBundle:Course:join.html.twig',array(
            'teachers'=>$teachers
        ));
    }

    public function archiveAction(Request $request)
    {
        $conditions = array(
            'status'   => 'published',
            'parentId' => '0'
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseService()->searchCourseCount($conditions), 30
        );

        $courses = $this->getCourseService()->searchCourses(
            $conditions, 'latest',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = array();

        foreach ($courses as &$course) {
            $course['tags'] = $this->getTagService()->findTagsByIds($course['tags']);
            $userIds        = array_merge($userIds, $course['teacherIds']);
        }

        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('TopxiaWebBundle:Course:archive.html.twig', array(
            'courses'   => $courses,
            'paginator' => $paginator,
            'users'     => $users
        ));
    }

    public function archiveCourseAction(Request $request, $id)
    {
        $course   = $this->getCourseService()->getCourse($id);
        $lessons  = $this->getCourseService()->searchLessons(array('courseId' => $course['id'], 'status' => 'published'), array('createdTime', 'ASC'), 0, 1000);
        $tags     = $this->getTagService()->findTagsByIds($course['tags']);
        $category = $this->getCategoryService()->getCategory($course['categoryId']);

        if (!$course) {
            $courseDescription = array();
        } else {
            $courseDescription = $course['about'];
            $courseDescription = strip_tags($courseDescription, '');
            $courseDescription = preg_replace("/ /", "", $courseDescription);
            $courseDescription = substr($courseDescription, 0, 100);
        }

        return $this->render('TopxiaWebBundle:Course:archiveCourse.html.twig', array(
            'course'            => $course,
            'lessons'           => $lessons,
            'tags'              => $tags,
            'category'          => $category,
            'courseDescription' => $courseDescription
        ));
    }

    public function archiveLessonAction(Request $request, $id, $lessonId)
    {
        $course = $this->getCourseService()->getCourse($id);

        $lessons = $this->getCourseService()->searchLessons(array('courseId' => $course['id'], 'status' => 'published'), array('createdTime', 'ASC'), 0, 1000);

        $tags = $this->getTagService()->findTagsByIds($course['tags']);

        if ($lessonId == '' && $lessons != null) {
            $currentLesson = $lessons[0];
        } else {
            $currentLesson = $this->getCourseService()->getCourseLesson($course['id'], $lessonId);
        }

        return $this->render('TopxiaWebBundle:Course:old_archiveLesson.html.twig', array(
            'course'        => $course,
            'lessons'       => $lessons,
            'currentLesson' => $currentLesson,
            'tags'          => $tags
        ));
    }

    public function infoAction(Request $request, $id)
    {
        list($course, $member) = $this->buildCourseLayoutData($request, $id);

        if ($course['parentId']) {
            $classroom = $this->getClassroomService()->findClassroomByCourseId($course['id']);

            if (!$this->getClassroomService()->canLookClassroom($classroom['classroomId'])) {
                return $this->createMessageResponse('info', '非常抱歉，您无权限访问该班级，如有需要请联系客服', '', 3, $this->generateUrl('homepage'));
            }
        }

        $category = $this->getCategoryService()->getCategory($course['categoryId']);
        $tags     = $this->getTagService()->findTagsByIds($course['tags']);

        return $this->render('TopxiaWebBundle:Course:info.html.twig', array(
            'course'   => $course,
            'member'   => $member,
            'category' => $category,
            'tags'     => $tags
        ));
    }

    public function membersAction(Request $request, $id)
    {
        list($course, $member) = $this->getCourseService()->tryTakeCourse($id);

        $paginator = new Paginator(
            $request,
            $this->getCourseService()->getCourseStudentCount($course['id']),
            6
        );

        $students = $this->getCourseService()->findCourseStudents(
            $course['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $studentUserIds = ArrayToolkit::column($students, 'userId');
        $users          = $this->getUserService()->findUsersByIds($studentUserIds);
        $followingIds   = $this->getUserService()->filterFollowingIds($this->getCurrentUser()->id, $studentUserIds);

        $progresses = array();

        foreach ($students as $student) {
            $progresses[$student['userId']] = $this->calculateUserLearnProgress($course, $student);
        }

        return $this->render('TopxiaWebBundle:Course:members-modal.html.twig', array(
            'course'       => $course,
            'students'     => $students,
            'users'        => $users,
            'progresses'   => $progresses,
            'followingIds' => $followingIds,
            'paginator'    => $paginator,
            'canManage'    => $this->getCourseService()->canManageCourse($course['id'])
        ));
    }

    public function showAction(Request $request, $id)
    {

        if($this->isMobile()){
            return $this->forward('TopxiaWebBundle:Course:mShow', array('id' =>$id));
        }

        list($course, $member) = $this->buildCourseLayoutData($request, $id);

        if ($course['parentId']) {
            $classroom = $this->getClassroomService()->findClassroomByCourseId($course['id']);

            if (!$this->getClassroomService()->canLookClassroom($classroom['classroomId'])) {
                return $this->createMessageResponse('info', '非常抱歉，您无权限访问该班级，如有需要请联系客服', '', 3, $this->generateUrl('homepage'));
            }
        }

        if (empty($member)) {
            $user   = $this->getCurrentUser();
            $member = $this->getCourseService()->becomeStudentByClassroomJoined($id, $user->id);

            if (isset($member["id"])) {
                $course['studentNum']++;
            }
        }

        $this->getCourseService()->hitCourse($id);

        $items       = $this->getCourseService()->getCourseItems($course['id']);
        $courseAbout = $course['about'];

        $courseAbout = strip_tags($courseAbout, '');

        $courseAbout = preg_replace("/ /", "", $courseAbout);
        $courseAbout = substr($courseAbout, 0, 100);

        if($course['type']=='travel'){
            $course['price']=intval( $course['price']);
            $course['travelDetail']=json_decode($course['travelDetail'],true);
            $course['users'] = empty($course['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($course['teacherIds']);
            $course['navigations'] = $this->getNavigationService()->getOpenedNavigationsTreeByType('top');
            foreach ($course['users'] as $key => $user) {
                $course['users'][$key]['profile'] = $this->getUserService()->getUserProfile($key);
                $course['users'][$key]['profile']['about'] = strip_tags($course['users'][$key]['profile']['about'], '');
                $course['users'][$key]['profile']['about'] = preg_replace("/ /", "", $course['users'][$key]['profile']['about']);
                $course['users'][$key]['profile']['about'] = str_replace(PHP_EOL, '', $course['users'][$key]['profile']['about']);
                $temp=$this->getUserService()->getUser($key);
                $course['users'][$key]['profile']['title'] =$temp['title'];
            }
            //调用小组最新主题
            $threads=$this->getGroupThreadService()->searchThreads(
                array('status'=>'open'),
                array(array('createdTime','DESC')),
                0,
                10);
            foreach($threads as  $key => $perGroup ){
                $soContent = $perGroup['content'];
                $soImages = '~<img [^>]* />~';
                preg_match_all( $soImages, $soContent, $thePics );
                if(count($thePics[0])!=0) {
                    $allPics = count($thePics[0]);
                    preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i', $thePics[0][0], $match);
                    $threads[$key]['thread_pic'] = $match[1];
                } else {
                    unset($threads[$key]);
                }
            }
            $course['threads']=$threads;
            //调用微信图片
            $block = $this->getBlock()->getBlockByCode('yingxiang:bottom_info');
            if(isset($block['data']['weixin'][0]['src'])){
                $course['weixin']=$block['data']['weixin'][0]['src'];
            }
            if(isset($block['data']['apple'][0]['src'])){
                $course['apple']=$block['data']['apple'][0]['src'];
            }
            //输出直播课时
            $lessons  = $this->getCourseService()->searchLessons(array('courseId' => $course['id'], 'status' => 'published','type'=>'live'), array('number', 'ASC'), 0, 1000);
            foreach ($lessons as  $key => $val){
                $lessons[$key]['mon']= date("m", $lessons[$key]['startTime']);
                $lessons[$key]['day']= date("d", $lessons[$key]['startTime']);
                $lessons[$key]['startHour']= date("H:i", $lessons[$key]['startTime']);
                $lessons[$key]['endHour']= date("H:i", $lessons[$key]['endTime']);
                if($lessons[$key]['startTime']>time()){
                    $lessons[$key]['status']='未开始';
                }else{
                    if($lessons[$key]['endTime']>time()){
                        $lessons[$key]['status']='进行中';
                    }else{
                        $lessons[$key]['status']='已结束';
                    }
                }
            }
            $course['lessons']=$lessons;
            //输出赠送课程
            $courses = $this->getClassroomService()->findActiveCoursesByTravelId($id);
            foreach ($courses as $kk => $val){
                $courses[$kk]['users'] = empty($val['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($val['teacherIds']);
            }
            $course['gift']=$courses;
            //输出最热门课程
            $topConditions= array(
                'type'           => 'travel',
                'recommended'    => null,
                'parentId'       => '0',
                'status'         => 'published'
            );

            $topCourses = $this->getCourseService()->searchCourses(
                $topConditions,
                'studentNum',
                0,
                5
            );
            $course['topCourses']=$topCourses;
            //生成播放连接
            $files = $this->getUploadFileService()->searchFiles(
                array('targetType' => 'courselesson', 'targetId' => $course['id']),
                'latestCreated',
                0,
                1);
            if(count($files)!=0){
                $file = $this->getUploadFileService()->getFile($files[0]['id']);
                $playUrl='';
                if ($file['storage'] == 'cloud') {
                    if (!empty($file['metas2']) && !empty($file['metas2']['sd']['key'])) {
                        if (isset($file['convertParams']['convertor']) && ($file['convertParams']['convertor'] == 'HLSEncryptedVideo')) {
                            $token = $this->makeToken('hls.playlist', $file['id'], array());
                            $params = array(
                                'id'    => $file['id'],
                                'token' => $token['token']
                            );
                            $playUrl=$this->generateUrl('hls_playlist', $params, true);
                        }
                    }
                }
                $course['playUrl']=$playUrl;
//                var_dump($playUrl);exit;
            }

            //是否关注
            if ($this->getCurrentUser()->isLogin()) {
                $course['isFollowed'] = $this->getUserService()->isFollowed($this->getCurrentUser()->id, $course['teacherIds'][0]);
            } else {
                $isFollowed = false;
            }

            //个人作品
            $threads=$this->getGroupThreadService()->searchThreads(
                array('userId' => $course['teacherIds'][0]),
                array(array('createdTime','DESC')),
                0,
                3
            );
            foreach($threads as  $key => $perGroup ){
                $soContent = $perGroup['content'];
                $soImages = '~<img [^>]* />~';
                preg_match_all( $soImages, $soContent, $thePics );
                $liked = $this->getGroupThreadService()->isliked($user['id'],$perGroup['id']);
                $threads[$key]['liked'] = $liked;
                if(count($thePics[0])!=0) {
                    $allPics = count($thePics[0]);
                    preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i', $thePics[0][0], $match);
                    $threads[$key]['thread_pic'] = $match[1];
                } else {
                    unset($threads[$key]);
                }
            }
            $course['thread']=$threads;

            //花絮
//            $category = $this->getCategoryService()->getCategoryByCode('YXW000103');
//
//            $articles = $this->getArticleService()->searchArticles(
//                array(
//                    'categoryId'      => $category['id'],
//                    'includeChildren' => true,
//                    'status'          => 'published'
//                ),
//                'studentNum',
//                0,
//                6
//            );
            $conditions= array(
                'type'           => 'normal',
                'parentId'  =>0,
                'status'    => 'published',
            );
            $categoryArray             = $this->getCategoryService()->getCategoryByCode('YXW000103');
            $childrenIds               = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
            $categoryIds               = array_merge($childrenIds, array($categoryArray['id']));
            $conditions['categoryIds'] = $categoryIds;
            $articles = $this->getCourseService()->searchCourses(
                $conditions,
                "studentNum",
                0,
                6
            );


            $course['articles']=$articles;

//            //CDN设置
//            $cdnUrl='http://img.yxwps.com';
//            $course['about'] = preg_replace('/\<img(\s+)src=\"\/files\//', "<img src=\"{$cdnUrl}/files/", $course['about']);


        }




        return $this->render("TopxiaWebBundle:Course:{$course['type']}-show.html.twig", array(
            'course'      => $course,
            'member'      => $member,
            'items'       => $items,
            'courseAbout' => $courseAbout
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

    public function mShowAction(Request $request, $id)
    {
        list($course, $member) = $this->buildCourseLayoutData($request, $id);

        if ($course['parentId']) {
            $classroom = $this->getClassroomService()->findClassroomByCourseId($course['id']);

            if (!$this->getClassroomService()->canLookClassroom($classroom['classroomId'])) {
                return $this->createMessageResponse('info', '非常抱歉，您无权限访问该班级，如有需要请联系客服', '', 3, $this->generateUrl('homepage'));
            }
        }

        if (empty($member)) {
            $user   = $this->getCurrentUser();
            $member = $this->getCourseService()->becomeStudentByClassroomJoined($id, $user->id);

            if (isset($member["id"])) {
                $course['studentNum']++;
            }
        }

        $this->getCourseService()->hitCourse($id);

        $items       = $this->getCourseService()->getCourseItems($course['id']);
        $courseAbout = $course['about'];

        $courseAbout = strip_tags($courseAbout, '');

        $courseAbout = preg_replace("/ /", "", $courseAbout);
        $courseAbout = substr($courseAbout, 0, 100);


        if($course['type']=='normal' || $course['type']=='live'){

            $tags    = $this->getTagService()->findTagsByIds($course['tags']);
            if(count($tags)!=0){
                $course['tag']=$tags[0]['name'];
            }

            $course['users'] = empty($course['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($course['teacherIds']);

            //获取帖子列表

            $filters    = $this->getThreadSearchFilters($request);
            $conditions = $this->convertFiltersToConditions($course, $filters);

            $paginator = new Paginator(
                $request,
                $this->getThreadService()->searchThreadCount($conditions),
                20
            );

            $threads = $this->getThreadService()->searchThreads(
                $conditions,
                $filters['sort'],
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );

            foreach ($threads as $key => $thread) {
                $threads[$key]['sticky']         = $thread['isStick'];
                $threads[$key]['nice']           = $thread['isElite'];
                $threads[$key]['lastPostTime']   = $thread['latestPostTime'];
                $threads[$key]['lastPostUserId'] = $thread['latestPostUserId'];
            }

            $lessons = $this->getCourseService()->findLessonsByIds(ArrayToolkit::column($threads, 'lessonId'));
            $userIds = array_merge(
                ArrayToolkit::column($threads, 'userId'),
                ArrayToolkit::column($threads, 'latestPostUserId')
            );
            $threadUsers = $this->getUserService()->findUsersByIds($userIds);

            //留言


            $reviewPaginator = new Paginator(
                $this->get('request'),
                $this->getReviewService()->getCourseReviewCount($id)
                , 10
            );

            $reviews = $this->getReviewService()->findCourseReviews(
                $id,
                $reviewPaginator->getOffsetCount(),
                $reviewPaginator->getPerPageCount()
            );

            $user = $this->getCurrentUser();
            $userReview = $user->isLogin() ? $this->getReviewService()->getUserCourseReview($user['id'], $course['id']) : null;

            $reviewUsers = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));



            return $this->render("TopxiaWebBundle:Course:m-normal-course.html.twig", array(
                'course'      => $course,
                'member'      => $member,
                'items'       => $items,
                'courseAbout' => $courseAbout,
                'paginator' => $paginator,
                'threads'   => $threads,
                'threadUsers'  => $threadUsers,
                'reviewUsers'  => $reviewUsers,

                'reviewPaginator' => $reviewPaginator,
                'reviewSaveUrl' => $this->generateUrl('course_review_create', array('id' => $course['id'])),
                'userReview' => $userReview,
                'reviews' => $reviews,

                'filters'   => $filters,
                'lessons'   => $lessons,
                'target'    => array('type' => 'course', 'id' => $id)
            ));
        }



        if($course['type']=='travel'){

            $tags    = $this->getTagService()->findTagsByIds($course['tags']);
            if(count($tags)!=0){
                $course['tag']=$tags[0]['name'];
            }

            $course['price']=intval( $course['price']);
            $course['travelDetail']=json_decode($course['travelDetail'],true);
            $course['users'] = empty($course['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($course['teacherIds']);
            $course['navigations'] = $this->getNavigationService()->getOpenedNavigationsTreeByType('top');
            foreach ($course['users'] as $key => $user) {
                $course['users'][$key]['profile'] = $this->getUserService()->getUserProfile($key);
                $course['users'][$key]['profile']['about'] = strip_tags($course['users'][$key]['profile']['about'], '');
                $course['users'][$key]['profile']['about'] = preg_replace("/ /", "", $course['users'][$key]['profile']['about']);
                $course['users'][$key]['profile']['about'] = str_replace(PHP_EOL, '', $course['users'][$key]['profile']['about']);
                $temp=$this->getUserService()->getUser($key);
                $course['users'][$key]['profile']['title'] =$temp['title'];
            }
            //调用小组最新主题
            $threads=$this->getGroupThreadService()->searchThreads(
                array('status'=>'open'),
                array(array('createdTime','DESC')),
                0,
                10);
            foreach($threads as  $key => $perGroup ){
                $soContent = $perGroup['content'];
                $soImages = '~<img [^>]* />~';
                preg_match_all( $soImages, $soContent, $thePics );
                if(count($thePics[0])!=0) {
                    $allPics = count($thePics[0]);
                    preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i', $thePics[0][0], $match);
                    $threads[$key]['thread_pic'] = $match[1];
                } else {
                    unset($threads[$key]);
                }
            }
            $course['threads']=$threads;
            //调用微信图片
            $block = $this->getBlock()->getBlockByCode('yingxiang:bottom_info');
            if(isset($block['data']['weixin'][0]['src'])){
                $course['weixin']=$block['data']['weixin'][0]['src'];
            }
            if(isset($block['data']['apple'][0]['src'])){
                $course['apple']=$block['data']['apple'][0]['src'];
            }
            //输出直播课时
            $lessons  = $this->getCourseService()->searchLessons(array('courseId' => $course['id'], 'status' => 'published','type'=>'live'), array('number', 'ASC'), 0, 1000);
            foreach ($lessons as  $key => $val){
                $lessons[$key]['mon']= date("m", $lessons[$key]['startTime']);
                $lessons[$key]['day']= date("d", $lessons[$key]['startTime']);
                $lessons[$key]['startHour']= date("H:i", $lessons[$key]['startTime']);
                $lessons[$key]['endHour']= date("H:i", $lessons[$key]['endTime']);
                if($lessons[$key]['startTime']>time()){
                    $lessons[$key]['status']='未开始';
                }else{
                    if($lessons[$key]['endTime']>time()){
                        $lessons[$key]['status']='进行中';
                    }else{
                        $lessons[$key]['status']='已结束';
                    }
                }
            }
            $course['lessons']=$lessons;
            //输出赠送课程
            $courses = $this->getClassroomService()->findActiveCoursesByTravelId($id);
            foreach ($courses as $kk => $val){
                $courses[$kk]['users'] = empty($val['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($val['teacherIds']);
            }
            $course['gift']=$courses;
            //输出最热门课程
            $topConditions= array(
                'type'           => 'travel',
                'recommended'    => null,
                'parentId'       => '0',
                'status'         => 'published'
            );

            $topCourses = $this->getCourseService()->searchCourses(
                $topConditions,
                'studentNum',
                0,
                5
            );
            $course['topCourses']=$topCourses;
            //生成播放连接
            $files = $this->getUploadFileService()->searchFiles(
                array('targetType' => 'courselesson', 'targetId' => $course['id']),
                'latestCreated',
                0,
                1);
            if(count($files)!=0){
                $file = $this->getUploadFileService()->getFile($files[0]['id']);
                $playUrl='';
                if ($file['storage'] == 'cloud') {
                    if (!empty($file['metas2']) && !empty($file['metas2']['sd']['key'])) {
                        if (isset($file['convertParams']['convertor']) && ($file['convertParams']['convertor'] == 'HLSEncryptedVideo')) {
                            $token = $this->makeToken('hls.playlist', $file['id'], array());
                            $params = array(
                                'id'    => $file['id'],
                                'token' => $token['token']
                            );
                            $playUrl=$this->generateUrl('hls_playlist', $params, true);
                        }
                    }
                }
                $course['playUrl']=$playUrl;
//                var_dump($playUrl);exit;
            }

            //是否关注
            if ($this->getCurrentUser()->isLogin()) {
                $course['isFollowed'] = $this->getUserService()->isFollowed($this->getCurrentUser()->id, $course['teacherIds'][0]);
            } else {
                $isFollowed = false;
            }

            //个人作品
            $threads=$this->getGroupThreadService()->searchThreads(
                array('userId' => $course['teacherIds'][0]),
                array(array('createdTime','DESC')),
                0,
                3
            );
            foreach($threads as  $key => $perGroup ){
                $soContent = $perGroup['content'];
                $soImages = '~<img [^>]* />~';
                preg_match_all( $soImages, $soContent, $thePics );
                $liked = $this->getGroupThreadService()->isliked($user['id'],$perGroup['id']);
                $threads[$key]['liked'] = $liked;
                if(count($thePics[0])!=0) {
                    $allPics = count($thePics[0]);
                    preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i', $thePics[0][0], $match);
                    $threads[$key]['thread_pic'] = $match[1];
                } else {
                    unset($threads[$key]);
                }
            }
            $course['thread']=$threads;

            //花絮


            $conditions= array(
                'type'           => 'normal',
                'parentId'  =>0,
                'status'    => 'published',
            );
            $categoryArray             = $this->getCategoryService()->getCategoryByCode('YXW000103');
            $childrenIds               = $this->getCategoryService()->findCategoryChildrenIds($categoryArray['id']);
            $categoryIds               = array_merge($childrenIds, array($categoryArray['id']));
            $conditions['categoryIds'] = $categoryIds;
            $articles = $this->getCourseService()->searchCourses(
                $conditions,
                "studentNum",
                0,
                6
            );


            $course['articles']=$articles;



            return $this->render("TopxiaWebBundle:Course:m-travel-course.html.twig", array(
                'course'      => $course,
                'member'      => $member,
                'items'       => $items,
                'courseAbout' => $courseAbout,
                'lessons'   => $lessons,
                'target'    => array('type' => 'course', 'id' => $id)
            ));



        }




        return $this->render("TopxiaWebBundle:Course:{$course['type']}-show.html.twig", array(
            'course'      => $course,
            'member'      => $member,
            'items'       => $items,
            'courseAbout' => $courseAbout
        ));
    }


    protected function getThreadSearchFilters($request)
    {
        $filters         = array();
        $filters['type'] = $request->query->get('type');

        if (!in_array($filters['type'], array('all', 'question', 'elite'))) {
            $filters['type'] = 'all';
        }

        $filters['sort'] = $request->query->get('sort');

        if (!in_array($filters['sort'], array('created', 'posted', 'createdNotStick', 'postedNotStick'))) {
            $filters['sort'] = 'posted';
        }

        return $filters;
    }

    protected function convertFiltersToConditions($course, $filters)
    {
        $conditions = array('courseId' => $course['id']);

        switch ($filters['type']) {
            case 'question':
                $conditions['type'] = 'question';
                break;
            case 'elite':
                $conditions['isElite'] = 1;
                break;
            default:
                break;
        }

        return $conditions;
    }


    public function productShowAction(Request $request, $id)
    {
        list($course, $member) = $this->buildCourseLayoutData($request, $id);

        if($course['type'] !='product'){
            return $this->createMessageResponse('info', '非常抱歉，该商品尚不存在', '', 3, $this->generateUrl('homepage'));
        }

        if($this->isMobile()){
            return $this->forward('TopxiaWebBundle:Course:mProductShow', array('id' =>$id));
        }

//        if (empty($member)) {
//            $user   = $this->getCurrentUser();
//            $member = $this->getCourseService()->becomeStudentByClassroomJoined($id, $user->id);
//
//            if (isset($member["id"])) {
//                $course['studentNum']++;
//            }
//        }

        $this->getCourseService()->hitCourse($id);

        $items       = $this->getCourseService()->getCourseItems($course['id']);
        $courseAbout = $course['about'];

        $courseAbout = strip_tags($courseAbout, '');

        $courseAbout = preg_replace("/ /", "", $courseAbout);
        $courseAbout = substr($courseAbout, 0, 100);
        $course['travelDetail']=json_decode($course['travelDetail'],true);

//        var_dump($course['travelDetail']);exit;

        //调用微信图片
        $block = $this->getBlock()->getBlockByCode('yingxiang:bottom_info');
        if(isset($block['data']['weixin'][0]['src'])){
            $course['weixin']=$block['data']['weixin'][0]['src'];
        }
        if(isset($block['data']['apple'][0]['src'])){
            $course['apple']=$block['data']['apple'][0]['src'];
        }
        //评价
        $user = $this->getCurrentUser();
        $userReview = $user->isLogin() ? $this->getReviewService()->getUserCourseReview($user['id'], $course['id']) : null;
        $reviews = $this->getReviewService()->findCourseReviews(
            $id,
            0,
            6
        );
        $reviewUsers = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));
        $topConditions= array(
            'type'           => 'product',
            'status'         => 'published'
        );

        $topCourses = $this->getCourseService()->searchCourses(
            $topConditions,
            'studentNum',
            0,
            4
        );
        //判断是否购买
        $isPay=false;
        if ($user->isLogin()) {
            $conditions['targetId'] = $course['id'];
            $conditions['userId']   = $user['id'];
            $count=$this->getOrderService()->searchOrderCount($conditions);
            if($count!=0){
                $isPay=true;
            }
        }

        //生成播放连接
        $files = $this->getUploadFileService()->searchFiles(
            array('targetType' => 'courselesson', 'targetId' => $course['id']),
            'latestCreated',
            0,
            1);
        if(count($files)!=0){
            $file = $this->getUploadFileService()->getFile($files[0]['id']);
            $playUrl='';
            if ($file['storage'] == 'cloud') {
                if (!empty($file['metas2']) && !empty($file['metas2']['sd']['key'])) {
                    if (isset($file['convertParams']['convertor']) && ($file['convertParams']['convertor'] == 'HLSEncryptedVideo')) {
                        $token = $this->makeToken('hls.playlist', $file['id'], array());
                        $params = array(
                            'id'    => $file['id'],
                            'token' => $token['token']
                        );
                        $playUrl=$this->generateUrl('hls_playlist', $params, true);
                    }
                }
            }
          $course['playUrl']=$playUrl;
        }




//        var_dump($isPay);exit;

        return $this->render("TopxiaWebBundle:Course:product-show.html.twig", array(
            'course'      => $course,
            'member'      => $member,
            'isPay'       =>$isPay,
            'reviewSaveUrl' => $this->generateUrl('course_review_create', array('id' => $course['id'])),
            'userReview' => $userReview,
            'items'       => $items,
            'courseAbout' => $courseAbout,
            'reviews' => $reviews,
            'users' => $reviewUsers,
            'topCourses' => $topCourses,
        ));
    }


    public function mProductShowAction(Request $request, $id)
    {
        list($course, $member) = $this->buildCourseLayoutData($request, $id);

        $this->getCourseService()->hitCourse($id);

        $items       = $this->getCourseService()->getCourseItems($course['id']);
        $courseAbout = $course['about'];

        $courseAbout = strip_tags($courseAbout, '');

        $courseAbout = preg_replace("/ /", "", $courseAbout);
        $courseAbout = substr($courseAbout, 0, 100);
        $course['travelDetail']=json_decode($course['travelDetail'],true);

        $tags    = $this->getTagService()->findTagsByIds($course['tags']);
        if(count($tags)!=0){
            $course['tag']=$tags[0]['name'];
        }

        $course['users'] = empty($course['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($course['teacherIds']);
        foreach ($course['users'] as $key => $user) {
            $course['users'][$key]['profile'] = $this->getUserService()->getUserProfile($key);
            $course['users'][$key]['profile']['about'] = strip_tags($course['users'][$key]['profile']['about'], '');
            $course['users'][$key]['profile']['about'] = preg_replace("/ /", "", $course['users'][$key]['profile']['about']);
            $course['users'][$key]['profile']['about'] = str_replace(PHP_EOL, '', $course['users'][$key]['profile']['about']);
            $temp=$this->getUserService()->getUser($key);
            $course['users'][$key]['profile']['title'] =$temp['title'];
        }


        //评价
        $user = $this->getCurrentUser();
        $userReview = $user->isLogin() ? $this->getReviewService()->getUserCourseReview($user['id'], $course['id']) : null;
        $reviews = $this->getReviewService()->findCourseReviews(
            $id,
            0,
            6
        );
        $reviewUsers = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));

        //判断是否购买
        $isPay=false;
        if ($user->isLogin()) {
            $conditions['targetId'] = $course['id'];
            $conditions['userId']   = $user['id'];
            $count=$this->getOrderService()->searchOrderCount($conditions);
            if($count!=0){
                $isPay=true;
            }
        }

        return $this->render("TopxiaWebBundle:Course:m-product.html.twig", array(
            'course'      => $course,
            'member'      => $member,
            'isPay'       =>$isPay,
            'reviewSaveUrl' => $this->generateUrl('course_review_create', array('id' => $course['id'])),
            'userReview' => $userReview,
            'items'       => $items,
            'courseAbout' => $courseAbout,
            'reviews' => $reviews,
            'users' => $reviewUsers,
        ));
    }





    public function keywordsAction($course)
    {
        $category = $this->getCategoryService()->getCategory($course['categoryId']);
        $tags     = $this->getTagService()->findTagsByIds($course['tags']);

        return $this->render('TopxiaWebBundle:Course:keywords.html.twig', array(
            'category' => $category,
            'tags'     => $tags,
            'course'   => $course
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

    public function favoriteAction(Request $request, $id)
    {
        $this->getCourseService()->favoriteCourse($id);

        return $this->createJsonResponse(true);
    }

    public function unfavoriteAction(Request $request, $id)
    {
        $this->getCourseService()->unfavoriteCourse($id);

        return $this->createJsonResponse(true);
    }

    public function createAction(Request $request)
    {
        $user        = $this->getUserService()->getCurrentUser();
        $userProfile = $this->getUserService()->getUserProfile($user['id']);

        $isLive = $request->query->get('flag');

        if($isLive == "isLive"){
            $type='live';
        }else{
            if($isLive == "isTravel"){
                $type='travel';
            }else{
                $type='normal';
            }
        }


//        $type   = ($isLive == "isLive") ? 'live' : 'normal';

        if ($type == 'live') {
            $courseSetting = $this->setting('course', array());

            if (empty($courseSetting['live_course_enabled'])) {
                return $this->createMessageResponse('info', '请前往后台开启直播,尝试创建！');
            }

            if (!empty($courseSetting['live_course_enabled'])) {
                $client   = new EdusohoLiveClient();
                $capacity = $client->getCapacity();
            } else {
                $capacity = array();
            }

            if (empty($capacity['capacity']) && !empty($courseSetting['live_course_enabled'])) {
                return $this->createMessageResponse('info', '请联系EduSoho官方购买直播教室，然后才能开启直播功能！');
            }
        }

        if (false === $this->get('security.context')->isGranted('ROLE_TEACHER')) {
            throw $this->createAccessDeniedException();
        }

        if ($request->getMethod() == 'POST') {
            $course = $request->request->all();
            $course = $this->getCourseService()->createCourse($course);

            return $this->redirect($this->generateUrl('course_manage', array('id' => $course['id'])));
        }


        return $this->render('TopxiaWebBundle:Course:create.html.twig', array(
            'userProfile' => $userProfile,
            'type'        => $type
        ));
    }

    public function productCreateAction(Request $request)
    {
        $user        = $this->getUserService()->getCurrentUser();
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $type='product';

        if (false === $this->get('security.context')->isGranted('ROLE_TEACHER')) {
            throw $this->createAccessDeniedException();
        }

        if ($request->getMethod() == 'POST') {
            $course = $request->request->all();
            $course = $this->getCourseService()->createCourse($course);
            return $this->redirect($this->generateUrl('course_manage', array('id' => $course['id'])));
        }


        return $this->render('TopxiaWebBundle:Course:create.html.twig', array(
            'userProfile' => $userProfile,
            'type'        => $type
        ));
    }

    public function exitAction(Request $request, $id)
    {
        list($course, $member) = $this->getCourseService()->tryTakeCourse($id);
        $user                  = $this->getCurrentUser();

        if (empty($member)) {
            throw $this->createAccessDeniedException('您不是课程的学员。');
        }

        if ($member["joinedType"] == "course" && !empty($member['orderId'])) {
            throw $this->createAccessDeniedException('有关联的订单，不能直接退出学习。');
        }

        $this->getCourseService()->removeStudent($course['id'], $user['id']);

        return $this->createJsonResponse(true);
    }

    public function becomeUseMemberAction(Request $request, $id)
    {
        if (!$this->setting('vip.enabled')) {
            $this->createAccessDeniedException();
        }

        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            $this->createAccessDeniedException();
        }

        $this->getCourseService()->becomeStudent($id, $user['id'], array('becomeUseMember' => true));

        return $this->createJsonResponse(true);
    }

    public function learnAction(Request $request, $id)
    {
        $user      = $this->getCurrentUser();
        $starttime = $request->query->get('starttime', '');

        if (!$user->isLogin()) {
            $request->getSession()->set('_target_path', $this->generateUrl('course_show', array('id' => $id)));

            return $this->createMessageResponse('info', '你好像忘了登录哦？', null, 3000, $this->generateUrl('login'));
        }

        $course = $this->getCourseService()->getCourse($id);

        if (empty($course)) {
            throw $this->createNotFoundException("课程不存在，或已删除。");
        }

        if ($course['approval'] == 1 && ($user['approvalStatus'] != 'approved')) {
            return $this->createMessageResponse('info', "该课程需要通过实名认证，你还没有通过实名认证。", null, 3000, $this->generateUrl('course_show', array('id' => $id)));
        }

        if (!$this->getCourseService()->canTakeCourse($id)) {
            return $this->createMessageResponse('info', "您还不是课程《{$course['title']}》的学员，请先购买或加入学习。", null, 3000, $this->generateUrl('course_show', array('id' => $id)));
        }

        try {
            list($course, $member) = $this->getCourseService()->tryTakeCourse($id);

            if ($member && !$this->getCourseService()->isMemberNonExpired($course, $member)) {
                return $this->redirect($this->generateUrl('course_show', array('id' => $id)));
            }

            if ($member && $member['levelId'] > 0) {
                if ($this->getVipService()->checkUserInMemberLevel($member['userId'], $course['vipLevelId']) != 'ok') {
                    return $this->redirect($this->generateUrl('course_show', array('id' => $id)));
                }
            }
        } catch (Exception $e) {
            throw $this->createAccessDeniedException('抱歉，未发布课程不能学习！');
        }

        return $this->render('TopxiaWebBundle:Course:learn.html.twig', array(
            'course'    => $course,
            'starttime' => $starttime
        ));
    }

    public function recordLearningTimeAction(Request $request, $lessonId, $time)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            $this->createAccessDeniedException();
        }

        $this->getCourseService()->waveLearningTime($user['id'], $lessonId, $time);

        return $this->createJsonResponse(true);
    }

    public function detailDataAction($id)
    {
        $course = $this->getCourseService()->tryManageCourse($id);

        $count     = $this->getCourseService()->getCourseStudentCount($id);
        $paginator = new Paginator($this->get('request'), $count, 20);

        $students = $this->getCourseService()->findCourseStudents($id, $paginator->getOffsetCount(), $paginator->getPerPageCount());

        foreach ($students as $key => $student) {
            $user                       = $this->getUserService()->getUser($student['userId']);
            $students[$key]['nickname'] = $user['nickname'];

            $questionCount                   = $this->getThreadService()->searchThreadCount(array('courseId' => $id, 'type' => 'question', 'userId' => $user['id']));
            $students[$key]['questionCount'] = $questionCount;

            if ($student['learnedNum'] >= $course['lessonNum'] && $course['lessonNum'] > 0) {
                $finishLearn                   = $this->getCourseService()->searchLearns(array('courseId' => $id, 'userId' => $user['id'], 'sttaus' => 'finished'), array('finishedTime', 'DESC'), 0, 1);
                $students[$key]['fininshTime'] = $finishLearn[0]['finishedTime'];

                $students[$key]['fininshDay'] = intval(($finishLearn[0]['finishedTime'] - $student['createdTime']) / (60 * 60 * 24));
            } else {
                $students[$key]['fininshDay'] = intval((time() - $student['createdTime']) / (60 * 60 * 24));
            }

            $learnTime                   = $this->getCourseService()->searchLearnTime(array('userId' => $user['id'], 'courseId' => $id));
            $students[$key]['learnTime'] = $learnTime;
        }

        return $this->render('TopxiaWebBundle:Course:course-data-modal.html.twig', array(
            'course'    => $course,
            'paginator' => $paginator,
            'students'  => $students
        ));
    }

    public function recordWatchingTimeAction(Request $request, $lessonId, $time)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            $this->createAccessDeniedException();
        }

        $learn = $this->getCourseService()->waveWatchingTime($user['id'], $lessonId, $time);

        $isLimit = $this->setting('magic.lesson_watch_limit');

        if ($isLimit) {
            $lesson         = $this->getCourseService()->getLesson($lessonId);
            $course         = $this->getCourseService()->getCourse($lesson['courseId']);
            $watchLimitTime = $course['watchLimit'] * $lesson['length'];

            if ($lesson['type'] == 'video' && ($course['watchLimit'] > 0) && ($learn['watchTime'] >= $watchLimitTime)) {
                $learn['watchLimited'] = true;
            }
        }

        return $this->createJsonResponse($learn);
    }

    public function addMemberExpiryDaysAction(Request $request, $courseId, $userId)
    {
        $user   = $this->getUserService()->getUser($userId);
        $course = $this->getCourseService()->getCourse($courseId);

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            $this->getCourseService()->addMemberExpiryDays($courseId, $userId, $fields['expiryDay']);

            return $this->createJsonResponse(true);
        }

        $default = $this->getSettingService()->get('default', array());

        return $this->render('TopxiaWebBundle:CourseStudentManage:set-expiryday-modal.html.twig', array(
            'course'  => $course,
            'user'    => $user,
            'default' => $default
        ));
    }

    /**
     * Block Actions.
     */
    public function headerAction($course, $manage = false)
    {
        $user = $this->getCurrentUser();

        $member = $this->getCourseService()->getCourseMember($course['id'], $user['id']);

        $users = empty($course['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($course['teacherIds']);

        if (empty($member)) {
            $member['deadline'] = 0;
            $member['levelId']  = 0;
        }

        $isNonExpired = $this->getCourseService()->isMemberNonExpired($course, $member);

        if ($member['levelId'] > 0) {
            $vipChecked = $this->getVipService()->checkUserInMemberLevel($user['id'], $course['vipLevelId']);
        } else {
            $vipChecked = 'ok';
        }

        if ($this->isBecomeStudentFromCourse($member)
            || $this->isBecomeStudentFromClassroomButExitedClassroom($course, $member, $user)) {
            $canExit = true;
        } else {
            $canExit = false;
        }

        return $this->render('TopxiaWebBundle:Course:header.html.twig', array(
            'course'       => $course,
            'canManage'    => $this->getCourseService()->canManageCourse($course['id']),
            'canExit'      => $canExit,
            'member'       => $member,
            'users'        => $users,
            'manage'       => $manage,
            'isNonExpired' => $isNonExpired,
            'vipChecked'   => $vipChecked,
            'isAdmin'      => $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')
        ));
    }

    private function isBecomeStudentFromCourse($member)
    {
        return isset($member["role"]) && isset($member["joinedType"]) && $member["role"] == 'student' && $member["joinedType"] == 'course';
    }

    private function isBecomeStudentFromClassroomButExitedClassroom($course, $member, $user)
    {
        $classroomMembers     = $this->getClassroomService()->getClassroomMembersByCourseId($course["id"], $user->id);
        $classroomMemberRoles = ArrayToolkit::column($classroomMembers, "role");

        return isset($member["joinedType"]) && $member["joinedType"] == 'classroom' && (empty($classroomMemberRoles) || count($classroomMemberRoles) == 0);
    }

    public function teachersBlockAction($course)
    {
        $users    = $this->getUserService()->findUsersByIds($course['teacherIds']);
        $profiles = $this->getUserService()->findUserProfilesByIds($course['teacherIds']);

        return $this->render('TopxiaWebBundle:Course:teachers-block.html.twig', array(
            'course'   => $course,
            'users'    => $users,
            'profiles' => $profiles
        ));
    }

    public function progressBlockAction($course)
    {
        $user = $this->getCurrentUser();

        $member          = $this->getCourseService()->getCourseMember($course['id'], $user['id']);
        $nextLearnLesson = $this->getCourseService()->getUserNextLearnLesson($user['id'], $course['id']);

        $progress = $this->calculateUserLearnProgress($course, $member);

        return $this->render('TopxiaWebBundle:Course:progress-block.html.twig', array(
            'course'          => $course,
            'member'          => $member,
            'nextLearnLesson' => $nextLearnLesson,
            'progress'        => $progress
        ));
    }

    public function latestMembersBlockAction($course, $count = 10)
    {
        $students = $this->getCourseService()->findCourseStudents($course['id'], 0, 12);
        $users    = $this->getUserService()->findUsersByIds(ArrayToolkit::column($students, 'userId'));

        return $this->render('TopxiaWebBundle:Course:latest-members-block.html.twig', array(
            'students' => $students,
            'users'    => $users
        ));
    }

    public function coursesBlockAction($courses, $view = 'list', $mode = 'default')
    {
        $userIds = array();

        foreach ($courses as $key => $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);

            $classroomIds = $this->getClassroomService()->findClassroomIdsByCourseId($course['id']);

            $courses[$key]['classroomCount'] = count($classroomIds);

            if (count($classroomIds) > 0) {
                $classroom                  = $this->getClassroomService()->getClassroom($classroomIds[0]);
                $courses[$key]['classroom'] = $classroom;
            }
        }

        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render("TopxiaWebBundle:Course:courses-block-{$view}.html.twig", array(
            'courses'      => $courses,
            'users'        => $users,
            'classroomIds' => $classroomIds,
            'mode'         => $mode
        ));
    }

    public function selectAction(Request $request)
    {
        $url         = "";
        $type        = "";
        $classroomId = 0;

        if ($request->query->get('url')) {
            $url = $request->query->get('url');
        }

        if ($request->query->get('type')) {
            $type = $request->query->get('type');
        }

        if ($request->query->get('classroomId')) {
            $classroomId = $request->query->get('classroomId');
        }

        $conditions = array(
            'status'   => 'published',
            'parentId' => 0
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseService()->searchCourseCount($conditions), 5
        );

        $courses = $this->getCourseService()->searchCourses(
            $conditions, 'latest',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $courseIds          = ArrayToolkit::column($courses, 'id');
        $unEnabledCourseIds = $this->getClassroomCourseIds($request, $courseIds);

        $userIds = array();

        foreach ($courses as &$course) {
            $course['tags'] = $this->getTagService()->findTagsByIds($course['tags']);
            $userIds        = array_merge($userIds, $course['teacherIds']);
        }

        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render("TopxiaWebBundle:Course:course-pick.html.twig", array(
            'users'              => $users,
            'url'                => $url,
            'courses'            => $courses,
            'type'               => $type,
            'unEnabledCourseIds' => $unEnabledCourseIds,
            'classroomId'        => $classroomId,
            'paginator'          => $paginator
        ));
    }

    protected function getClassroomCourseIds($request, $courseIds)
    {
        $unEnabledCourseIds = array();

        if ($request->query->get('type') != "classroom") {
            return $unEnabledCourseIds;
        }

        $classroomId = $request->query->get('classroomId');

        foreach ($courseIds as $key => $value) {
            $course     = $this->getCourseService()->getCourse($value);
            $classrooms = $this->getClassroomService()->findClassroomIdsByCourseId($value);

            if ($course && count($classrooms) == 0) {
                unset($courseIds[$key]);
            }
        }

        $unEnabledCourseIds = $courseIds;

        return $unEnabledCourseIds;
    }

    public function relatedCoursesBlockAction($course)
    {
        $courses = $this->getCourseService()->findNormalCoursesByAnyTagIdsAndStatus($course['tags'], 'published', array('rating desc,recommendedTime desc ,createdTime desc', ''), 0, 4);

        return $this->render("TopxiaWebBundle:Course:related-courses-block.html.twig", array(
            'courses'       => $courses,
            'currentCourse' => $course
        ));
    }

    public function rebuyAction(Request $request, $courseId)
    {
        $user = $this->getCurrentUser();

        $this->getCourseService()->removeStudent($courseId, $user['id']);

        return $this->redirect($this->generateUrl('course_show', array('id' => $courseId)));
    }

    public function listViewAction(Request $request, $courseId)
    {
        return $this->render('TopxiaWebBundle:Course:list-view.html.twig', array(

        ));
    }

    public function memberIdsAction(Request $request, $id)
    {
        $ids = $this->getCourseService()->findMemberUserIdsByCourseId($id);

        return $this->createJsonResponse($ids);
    }

    public function qrcodeAction(Request $request, $id)
    {
        $user  = $this->getUserService()->getCurrentUser();
        $host  = $request->getSchemeAndHttpHost();
        $token = $this->getTokenService()->makeToken('qrcode', array(
            'userId'   => $user['id'],
            'data'     => array(
                'url'    => $this->generateUrl('course_show', array('id' => $id), true),
                'appUrl' => "{$host}/mapi_v2/mobile/main#/course/{$id}"
            ),
            'times'    => 0,
            'duration' => 3600
        ));
        $url = $this->generateUrl('common_parse_qrcode', array('token' => $token['token']), true);

        $response = array(
            'img' => $this->generateUrl('common_qrcode', array('text' => $url), true)
        );
        return $this->createJsonResponse($response);
    }

  protected function getCourseTeachersAndCategories($courses)
  {
    $userIds = array();
    $categoryIds = array();
    foreach ($courses as $course) {
      $userIds = array_merge($userIds, $course['teacherIds']);
      $categoryIds[] = $course['categoryId'];
    }

    $users = $this->getUserService()->findUsersByIds($userIds);
    $profiles = $this->getUserService()->findUserProfilesByIds($userIds);
    foreach ($users as $key => $user) {
      if ($user['id'] == $profiles[$user['id']]['id']) {
        $users[$key]['profile'] = $profiles[$user['id']];
      }
    }

    $categories = $this->getCategoryService()->findCategoriesByIds($categoryIds);

    foreach ($courses as &$course) {
      $teachers = array();
      foreach ($course['teacherIds'] as $teacherId) {
        $user = $users[$teacherId];
        unset($user['password']);
        unset($user['salt']);
        $teachers[] = $user;
      }
      $course['teachers'] = $teachers;

      $categoryId = $course['categoryId'];
      if($categoryId!=0 && array_key_exists($categoryId, $categories)) {
        $course['category'] = $categories[$categoryId];
      }
    }

    return $courses;
  }

    public function orderInfoAction(Request $request, $sn)
    {
        $order = $this->getOrderService()->getOrderBySn($sn);

        if (empty($order)) {
            throw $this->createNotFoundException('订单不存在!');
        }

        $course = $this->getCourseService()->getCourse($order['targetId']);

        if (empty($course)) {
            throw $this->createNotFoundException("课程不存在，或已删除。");
        }

        return $this->render('TopxiaWebBundle:Course:course-order.html.twig', array('order' => $order, 'course' => $course));
    }

    protected function makeToken($type, $fileId, $context = array())
    {
        $fileds = array(
            'data'     => array(
                'id' => $fileId
            ),
            'times'    => 3,
            'duration' => 3600,
            'userId'   => $this->getCurrentUser()->getId()
        );

        if (isset($context['watchTimeLimit'])) {
            $fileds['data']['watchTimeLimit'] = $context['watchTimeLimit'];
        }

        $token = $this->getTokenService()->makeToken($type, $fileds);
        return $token;
    }


    protected function getTokenService()
    {
        return $this->getServiceKernel()->createService('User.TokenService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getVipService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.VipService');
    }

    protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    protected function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Course.ThreadService');
    }

    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

    protected function getAppService()
    {
        return $this->getServiceKernel()->createService('CloudPlatform.AppService');
    }

    protected function getDiscountService()
    {
        return $this->getServiceKernel()->createService('Discount:Discount.DiscountService');
    }

    protected function getClassroomService()
    {
        return $this->getServiceKernel()->createService('Classroom:Classroom.ClassroomService');
    }

    public function getLevelService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.LevelService');
    }

    protected function getOrderService()
    {
        return $this->getServiceKernel()->createService('Order.OrderService');
    }

    protected function getArticleService()
    {
        return $this->getServiceKernel()->createService('Article.ArticleService');
    }

    protected function getNavigationService()
    {
        return $this->getServiceKernel()->createService('Content.NavigationService');
    }

    protected function getGroupService()
    {
        return $this->getServiceKernel()->createService('Group.GroupService');
    }

    protected function getGroupThreadService()
    {
        return $this->getServiceKernel()->createService('Group.ThreadService');
    }

    protected function getBlock()
    {
        return $this->getServiceKernel()->createService('Content.BlockService');
    }

    protected function getReviewService()
    {
        return $this->getServiceKernel()->createService('Course.ReviewService');
    }
}
