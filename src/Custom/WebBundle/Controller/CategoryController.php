<?php
namespace Custom\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\WebBundle\Controller\BaseController;

class CategoryController extends BaseController
{
    public function allAction()
    {
        $categories = $this->getCategoryService()->findCategories(1);

        $data = array();
        foreach ($categories as $category) {
            $data[$category['id']] = array($category['name'], $category['parentId']);
        }

        return $this->createJsonResponse($data);
    }

    public function treeNavAction(Request $request, $category, $path, $fliter = array('price'=>'all','type'=>'all', 'currentLevelId'=>'all'), $orderBy = 'latest')
    {
        list($rootCategories, $categories, $activeIds) = $this->getCategoryService()->makeNavCategories($category, 'course');

        if($path=='classroom_explore') {
            $expArray = array('摄影大咖', '旅游直播', '精彩回顾', '商品中心');
            foreach ($rootCategories as $kk => $vv) {
                if (in_array($vv['name'], $expArray)) {
                    unset($rootCategories[$kk]);
                }
            }
        }


        return $this->render("TopxiaWebBundle:Category:classroom-explore-nav.html.twig", array(
            'rootCategories' => $rootCategories,
            'categories' => $categories,
            'category' => $category,
            'path' => $path,
            'activeIds' => $activeIds,
            'fliter' => $fliter,
            'orderBy' => $orderBy
        ));
    }

    protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }
}
