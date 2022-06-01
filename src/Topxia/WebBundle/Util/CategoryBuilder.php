<?php
namespace Topxia\WebBundle\Util;

use Topxia\Service\Common\ServiceKernel;

class CategoryBuilder
{
	public function buildChoices($groupCode, $indent = '　' ,$type=null,$level=0)
	{


        $group = $this->getCategoryService()->getGroupByCode($groupCode);
        if (empty($group)) {
        	return array();
        }
        $choices = array();

        if($level==0){
            $categories = $this->getCategoryService()->getCategoryTree($group['id']);
            foreach ($categories as $category) {
                $choices[$category['id']] = str_repeat(is_null($indent) ? '' : $indent, ($category['depth']-1)) . $category['name'];
            }
            return $choices;
        }else{

            $categories = $this->getCategoryService()->getCategoryTree($group['id']);

            $levelCategories=array();
            foreach ($categories as $category){

                if($category['depth']==1){
                    $block=$category['name'];
                    $levelCategories[$category['name']]=array();
                    $choices[$category['id']] = $category['name'];
                }
                if($category['depth']==2){
                    $blockB=$category['name'];
                    $levelCategories[$block][$category['name']]=array();
                }
                if($category['depth']==3){
                    $levelCategories[$block][$blockB][$category['id']]=$category['name'];
                }
            }

            return $levelCategories;
        }

	}

    private function getCategoryService()
    {
        return ServiceKernel::instance()->createService('Taxonomy.CategoryService');
    }
}
    