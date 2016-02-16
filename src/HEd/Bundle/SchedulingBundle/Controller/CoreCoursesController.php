<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreCoursesController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Course');
    $course = array();
    if ($this->record->getSelectedRecordID()) {
      
      // Get Rooms
      $course = $this->db()->db_select('STUD_COURSE')
        ->fields('STUD_COURSE')
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
    }
    
    return $this->render('KulaHEdSchedulingBundle:CoreCourses:index.html.twig', array('course' => $course));
  }
  
  public function prerequisitesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Course');
    $prerequisites = array();
    $course_info = array();
    if ($this->record->getSelectedRecordID()) {
      
      // Get course info
      $course_info = $this->db()->db_select('STUD_COURSE')
        ->fields('STUD_COURSE', array('MARK_SCALE_ID'))
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      
      // Get Prerequisites
      $prerequisites = $this->db()->db_select('STUD_COURSE_PREREQUISITES')
        ->fields('STUD_COURSE_PREREQUISITES')
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdSchedulingBundle:CoreCourses:prerequisites.html.twig', array('course_info' => $course_info, 'prerequisites' => $prerequisites));
  }
  
  public function corequisitesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Course');
    $corequisites = array();
    if ($this->record->getSelectedRecordID()) {
      
      $query_conditions = $this->db()->db_or();
      $query_conditions = $query_conditions->condition('COURSE_ID', $this->record->getSelectedRecordID());
      $query_conditions = $query_conditions->condition('COREQUISITE_COURSE_ID', $this->record->getSelectedRecordID());
      
      // Get Rooms
      $corequisites = $this->db()->db_select('STUD_COURSE_COREQUISITES')
        ->fields('STUD_COURSE_COREQUISITES')
        ->condition($query_conditions)
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdSchedulingBundle:CoreCourses:corequisites.html.twig', array('corequisites' => $corequisites));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Course', 'Y');
    $this->formAction('Core_HEd_Course_Course_Create');
    return $this->render('KulaHEdSchedulingBundle:CoreCourses:add.html.twig');
  }
  
  public function createAction() {
    $this->authorize();
    $this->processForm();
    $id = $this->poster()->getResult();
    return $this->forward('core_HEd_courses', array('record_type' => 'Core.HEd.Course', 'record_id' => $id), array('record_type' => 'Core.HEd.Course', 'record_id' => $id));
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Course');
    
    $rows_affected = $this->db()->db_delete('STUD_COURSE')
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())->execute();
    
    if ($rows_affected == 1) {
      $this->addFlash('success', 'Deleted course.');
    }
    
    return $this->forward('core_HEd_courses');
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('HEd.Course')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
  public function combineAction() {
    $this->authorize();
    
    $combine = $this->request->request->get('non');
    if (isset($combine['HEd.Course']['delete']['HEd.Course.ID']['value']) AND isset($combine['HEd.Course']['keep']['HEd.Course.ID']['value'])) {
      
      if ($result = $this->get('kula.Core.Combine')->combine('STUD_COURSE', $combine['HEd.Course']['delete']['HEd.Course.ID']['value'], $combine['HEd.Course']['keep']['HEd.Course.ID']['value'])) {
        $this->addFlash('success', 'Combined courses.');
      } else {
        $this->addFlash('error', 'Unable to combined courses.');
      }
      
    }

    return $this->render('KulaHEdSchedulingBundle:CoreCourses:combine.html.twig');
  }
  
}