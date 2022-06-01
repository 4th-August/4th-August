<?php

namespace Classroom\Service\Classroom\Dao;

interface ClassroomCourseDao
{
    public function addCourse($course,$isTravel=false);

    public function update($id, $fields);

    public function updateTravel($id, $fields);

    public function updateByParam($params, $fields);

    public function updateTravelByParam($params, $fields);

    public function deleteCourseByClassroomIdAndCourseId($classroomId, $courseId);

    public function deleteCourseByTravelIdAndCourseId($travelId, $courseId);

    public function getCourseByClassroomIdAndCourseId($classroomId, $courseId);

    public function searchCourses($conditions, $orderBy, $start, $limit);

    public function deleteCoursesByClassroomId($classroomId);

    public function findClassroomIdsByCourseId($courseId);

    public function findClassroomByCourseId($courseId);

    public function findCoursesByClassroomIdAndCourseIds($classroomId, $courseIds);

    public function findClassroomCourse($classroomId, $courseId);

    public function findTravelCourse($travelId, $courseId);

    public function findCoursesByClassroomId($classroomId);

    public function findCoursesByTravelId($travelId);

    public function findActiveCoursesByClassroomId($classroomId);

    public function findActiveCoursesByTravelId($travelId);

    public function findCoursesByCoursesIds($courseIds);

    public function findClassroomsByCoursesIds($courseIds);
}
