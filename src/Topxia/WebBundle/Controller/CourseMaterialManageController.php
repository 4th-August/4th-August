<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class CourseMaterialManageController extends BaseController
{
    public function indexAction(Request $request, $courseId, $lessonId)
    {
        $course    = $this->getCourseService()->tryManageCourse($courseId);
        $lesson    = $this->getCourseService()->getCourseLesson($courseId, $lessonId);
        $materials = $this->getMaterialService()->findLessonMaterials($lesson['id'], 0, 100);
        return $this->render('TopxiaWebBundle:CourseMaterialManage:material-modal.html.twig', array(
            'course'         => $course,
            'lesson'         => $lesson,
            'materials'      => $materials,
            'storageSetting' => $this->setting('storage'),
            'targetType'     => 'coursematerial',
            'targetId'       => $course['id']
        ));
    }

    public function uploadAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $lesson = $this->getCourseService()->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createNotFoundException();
        }

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            if (empty($fields['fileId']) && empty($fields['link'])) {
                throw $this->createNotFoundException();
            }

            $fields['courseId'] = $course['id'];
            $fields['lessonId'] = $lesson['id'];

            $material = $this->getMaterialService()->uploadMaterial($fields);

            return $this->render('TopxiaWebBundle:CourseMaterialManage:list-item.html.twig', array(
                'material' => $material
            ));
        }

        return $this->render('TopxiaWebBundle:CourseMaterial:upload-modal.html.twig', array(
            'form'   => $form->createView(),
            'course' => $course
        ));
    }


    public function pushAction(Request $request, $courseId, $lessonId)
    {
        $lesson    = $this->getCourseService()->getCourseLesson($courseId, $lessonId);
        $params=array('cid'=>$lesson['liveProvider']);

        //生成推流地址

        $key='/yxwps/'.$lesson['liveProvider'].'-'.$lesson['endTime'].'-0-0-6JD0pQydYT3BzBcmzQk2oaciAVhiIe3M';

        $pushUrl='rtmp://video-center.alivecdn.com/yxwps/'.$lesson['liveProvider'].'?vhost=live.yxwps.com&auth_key='.$lesson['endTime'].'-0-0-'.md5($key);

        $isPlay=0;
        if(time()>$lesson['endTime']){
            $isPlay=1;
        }

        return $this->render('TopxiaWebBundle:CourseMaterialManage:push-modal.html.twig', array(
            'pushUrl'         => $pushUrl,
            'isPlay'          => $isPlay
        ));
    }

    public function deleteAction(Request $request, $courseId, $lessonId, $materialId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $this->getMaterialService()->deleteMaterial($courseId, $materialId);
        return $this->createJsonResponse(true);
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    protected function getMaterialService()
    {
        return $this->getServiceKernel()->createService('Course.MaterialService');
    }

    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }
}
