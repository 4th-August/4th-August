<?php

namespace Topxia\WebBundle\Extensions\DataTag;

use Topxia\WebBundle\Extensions\DataTag\DataTag;

class GroupImgDataTag extends BaseDataTag implements DataTag
{
    /**
     * 获取最热小组
     *
     * 可传入的参数：
     *
     *   count 必需 必需 小组数量，取值不能超过100
     *
     * @param  array $arguments 参数
     * @return array 最热小组
     */

    public function getData(array $arguments)
    {
        if (empty($arguments['count'])) {
            throw new \InvalidArgumentException("count参数缺失");
        } else {
            $hotGroups = $this->getThreadService()->searchThreads(array('status'=>'open'), array(array('createdTime','DESC')),0, 50);
        }
        $threadNormal=array();
        $threadAbNormal = array();
        foreach($hotGroups as  $key => $perGroup ){

            $soContent = $perGroup['content'];
            $soImages = '~<img [^>]* />~';
            preg_match_all( $soImages, $soContent, $thePics );
            if(count($thePics[0])!=0){

            $allPics = count($thePics[0]);
            preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$thePics[0][0],$match);
            $imageSize = getimagesize ('http://www.16fs.com.cn'.$match[1]);
            $imageW = $imageSize[0];
            $imageH = $imageSize[1];
            if($imageW == 1000 && $imageH == 666) {
                $hotGroups[$key]['thread_pic'] = $match[1];
                $threadNormal[] = $hotGroups[$key];

            } else if ($imageW == 1000 && $imageH == 1345){
                $hotGroups[$key]['thread_pic'] = $match[1];
                $threadAbNormal[] = $hotGroups[$key];
            }

            }
        }
        $returnThread = array();
        if(count($threadAbNormal)!=0){
            foreach($threadNormal as $key=>$perThread) {
                if($key == 8) break;
                if($key == 2) {
                    $returnThread[] = $threadAbNormal[0];
                } else {
                    $returnThread[] = $perThread;
                }

            }
        } else {
            $returnThread = null;
        }

        if(count($returnThread) != 8){
            $returnThread = null;
        }

        return $returnThread;
    }

    private function getThreadService()
    {
        return $this->getServiceKernel()->createService('Group.ThreadService');
    }
}
