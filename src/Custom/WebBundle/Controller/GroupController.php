<?php

namespace Custom\WebBundle\Controller;

use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use Topxia\WebBundle\Controller\GroupController as BaseController;

class GroupController extends BaseController
{
    public function exploreAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $conditions    = $request->query->all();
        $activeGroup = $this->getGroupService()->searchGroups(array('status'=>'open',),  array('memberNum', 'DESC'),0, 12);
        $order = array(array('createdTime','DESC'));

        if(isset($conditions['orderBy'])){
            $order = array(array($conditions['orderBy'],'DESC'));
        }
        unset($conditions['orderBy']);
        $conditions['status']   =   'open';
        if(isset($conditions['groupId'])){
            $groupId = $conditions['groupId'];
        }
        else {
            unset($conditions['groupId']);
            $groupId = null;
        }

        $paginator              = new Paginator(
            $this->get('request'),
            $this->getThreadService()->searchThreadsCount($conditions),
            12
        );
        $threads = $this->getThreadService()->searchThreads(
            $conditions,
            $order,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        foreach($threads as  $key => $perGroup ){

            $soContent = $perGroup['content'];
            $soImages = '~<img [^>]* />~';
            preg_match_all( $soImages, $soContent, $thePics );
            $liked = $this->getThreadService()->isliked($user['id'],$perGroup['id']);
            $threads[$key]['liked'] = $liked;
            if(count($thePics[0])!=0) {
                $allPics = count($thePics[0]);
                preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i', $thePics[0][0], $match);
                $threads[$key]['thread_pic'] = $match[1];


            } else {
                unset($threads[$key]);
            }


        }

        $ownerIds = ArrayToolkit::column($threads, 'userId');
        $groupIds = ArrayToolkit::column($threads, 'groupId');
        $userIds =  ArrayToolkit::column($threads, 'lastPostMemberId');

        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);

        $owners=$this->getUserService()->findUsersByIds($ownerIds);

        $groups=$this->getGroupService()->getGroupsByids($groupIds);

        $user = $this->getCurrentUser();


        $orderBy = $order[0][0];
        return $this->render("TopxiaWebBundle:Group:explore.html.twig", array(
            'activeGroup' => $activeGroup,
            'lastPostMembers'=>$lastPostMembers,
            'owners'=>$owners,
            'groupinfo'=>$groups,
            'user'=>$user,
            'threads'=>$threads,
            'paginator'=>$paginator,
            'groupId'  =>$groupId,
            'orderBy'   =>$orderBy
        ));
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Group.ThreadService');
    }
}
