<?php

namespace Kula\K12\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreCoursesController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Course');
    $course = array();
    if ($this->record->getSelectedRecordID()) {
      
      // Get Rooms
      $course = $this->db()->db_select('STUD_COURSE')
        ->fields('STUD_COURSE')
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
    }
    
    return $this->render('KulaK12SchedulingBundle:CoreCourses:index.html.twig', array('course' => $course));
  }
  
  public function prerequisitesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Course');
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
    
    return $this->render('KulaK12SchedulingBundle:CoreCourses:prerequisites.html.twig', array('course_info' => $course_info, 'prerequisites' => $prerequisites));
  }
  
  public function corequisitesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Course');
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
    
    return $this->render('KulaK12SchedulingBundle:CoreCourses:corequisites.html.twig', array('corequisites' => $corequisites));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Course', 'Y');
    $this->formAction('Core_K12_Course_Course_Create');
    return $this->render('KulaK12SchedulingBundle:CoreCourses:add.html.twig');
  }
  
  public function createAction() {
    $this->authorize();
    $this->processForm();
    $id = $this->poster()->getResult();
    return $this->forward('Core_K12_Course_Course', array('record_type' => 'Core.K12.Course', 'record_id' => $id), array('record_type' => 'Core.K12.Course', 'record_id' => $id));
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Course');
    
    $rows_affected = $this->db()->db_delete('STUD_COURSE')
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())->execute();
    
    if ($rows_affected == 1) {
      $this->addFlash('success', 'Deleted course.');
    }
    
    return $this->forward('Core_K12_Course_Course');
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('K12.Course')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }

}