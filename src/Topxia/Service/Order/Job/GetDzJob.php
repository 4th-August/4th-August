<?php
namespace Topxia\Service\Order\Job;

use Topxia\Service\Crontab\Job;
use Topxia\Service\Common\ServiceKernel;

class GetDzJob implements Job
{
	public function execute($params)
    {
    	//解析DZ的数据
        $ch = curl_init("http://foto.yxwps.com/portal.php?mod=list&catid=3") ;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
        $output = curl_exec($ch) ;
        $n1=strpos($output,"ct2 wp cl mobanbus_list",0);

        $this->getCourseService()->rmDz();

        for ($i=1;$i<=5;$i++){

            $n2=strpos($output,"bus_postbd bus_sd",$n1);


            $n3=strpos($output,'href="',$n2);
            $n4=strpos($output,'" target',$n3+2);
            $link=substr($output, $n3+6, $n4-$n3-6);



            $n3=strpos($output,'<img src="',$n4);
            $n4=strpos($output,'"',$n3+10);
            $img=substr($output, $n3+10, $n4-$n3-10);
//    var_dump($img);exit;



//    var_dump($link);exit;
            $n3=strpos($output,'alt="',$n4);
            $n4=strpos($output,'>',$n3);
            $title=substr($output, $n3+5, $n4-$n3-9);

//    var_dump($title);exit;

            $n3=strpos($output,'bussummary">',$n4);
            $n4=strpos($output,"</p>",$n3);
            $content=preg_replace("{\r\n}","",substr($output, $n3+12, $n4-$n3-12));
            $content=str_replace(" ","",$content);


            $n1=$n4;
            $ch = curl_init($link) ;
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
            $output2 = curl_exec($ch) ;

            $n3=strpos($output2,'<em id="_viewnum">',0);
            $n4=strpos($output2,'</em>',$n3+18);
            $view_number=substr($output2, $n3+18, $n4-$n3-18);

            $conditions['img']=$img;
            $conditions['title']=$title;
            $conditions['content']=$content;
            $conditions['link']=$link;
            $conditions['view_number']=$view_number;
            $conditions['type']=1;

            $this->getCourseService()->addDz($conditions);
        }

//        http://foto.yxwps.com/forum.php?mod=forumdisplay&fid=51

        $ch = curl_init("http://foto.yxwps.com/forum.php?mod=forumdisplay&fid=51");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
        $output = curl_exec($ch) ;
        $n1=strpos($output,"mobanbus_item mobanbus_scroll",0);

        for ($i=1;$i<=12;$i++){

            $n2=strpos($output,"bus_postbd item masonry_brick",$n1);
            $n3=strpos($output,'href="',$n2);
            $n4=strpos($output,'"  onclick',$n2);
            $link=substr($output, $n3+6, $n4-$n3-6);

            $n3=strpos($output,'<img src="',$n2);
            $n4=strpos($output,'"',$n3+10);
            $img=substr($output, $n3+10, $n4-$n3-10);

            $n3=strpos($output,'alt="',$n4);
            $n4=strpos($output,'>',$n3);
            $title=substr($output, $n3+5, $n4-$n3-7);

            $n3=strpos($output,'<span>浏览：',$n4);
            $n4=strpos($output,'</span>',$n3);
            $view_number=substr($output, $n3+15, $n4-$n3-15);

            $n1=$n4;

            $conditions['img']=$img;
            $conditions['title']=$title;
            $conditions['content']='';
            $conditions['link']=str_replace('amp;','',$link);
            $conditions['view_number']=$view_number;
            $conditions['type']=2;

            $this->getCourseService()->addDz($conditions);


        }


    }

    protected function getCourseService()
    {
        return ServiceKernel::instance()->createService('Course.CourseService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

}
