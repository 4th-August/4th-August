<?php

namespace Custom\WebBundle\Controller;

use Topxia\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\WebBundle\Controller\DefaultController as BaseController;

class DefaultController extends BaseController
{

    public function coursesCategoryYouXueAction(Request $request)
    {
        $conditions             = $request->query->all();
        $conditions['status']   = 'published';
        $conditions['code'] = "YXW004";
        $categoryId             = isset($conditions['categoryId']) ? $conditions['categoryId'] : 0;

        if (isset($conditions['config'])) {
            $config = $conditions['config'];
            unset($conditions['config']);
        } else {
            $config = array();
            unset($conditions['config']);
        }

        if (!empty($conditions['categoryId'])) {
            $conditions['categoryId'] = intval($conditions['categoryId']);
        } else {
            unset($conditions['categoryId']);
        }

        $orderBy = $conditions['orderBy'];

        if ($orderBy == 'recommendedSeq') {
            $conditions['recommended'] = 1;
        }
        $categories = $this->getCategoryService()->getCategoryByCode($conditions['code']);

        unset($conditions['orderBy']);
        $config               = $this->getThemeService()->getCurrentThemeConfig();
        $config               = $config['confirmConfig']['blocks']['left'][0];
        $config['orderBy']    = $orderBy;
        $config['categoryId'] = $categoryId;
        return $this->render('TopxiaWebBundle:Default:course-grid-with-youxue-index.html.twig', array(
            'config' => $config,
            'categories' => $categories
        ));
    }
}
