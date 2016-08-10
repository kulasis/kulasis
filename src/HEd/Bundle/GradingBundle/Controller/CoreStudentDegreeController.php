<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreStudentDegreeController extends Controller {
  
  public function degreesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $degrees = array();
    
    if ($this->record->getSelectedRecordID()) {
    $degrees = $this->db()->db_select('STUD_STUDENT_DEGREES')
      ->fields('STUD_STUDENT_DEGREES', array('STUDENT_DEGREE_ID', 'EFFECTIVE_DATE', 'DEGREE_ID', 'DEGREE_AWARDED', 'EXPECTED_COMPLETION_TERM_ID', 'GRADUATION_DATE', 'CONFERRED_DATE'))
      ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();    
    }

    return $this->render('KulaHEdGradingBundle:CoreStudentDegree:degrees.html.twig', array('degrees' => $degrees));  
  }
  
  public function detailAction($id, $sub_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $degree = $this->db()->db_select('STUD_STUDENT_DEGREES')
      ->fields('STUD_STUDENT_DEGREES', array('STUDENT_DEGREE_ID', 'DEGREE_AWARDED', 'GRADUATION_DATE', 'CONFERRED_DATE', 'EXPECTED_GRADUATION_DATE'))
      ->join('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = STUD_STUDENT_DEGREES.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetch();
    
    $majors = $this->db()->db_select('STUD_STUDENT_DEGREES_MAJORS')
      ->fields('STUD_STUDENT_DEGREES_MAJORS', array('STUDENT_MAJOR_ID', 'STUDENT_DEGREE_ID', 'MAJOR_ID'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetchAll();
    
    $minors = $this->db()->db_select('STUD_STUDENT_DEGREES_MINORS')
      ->fields('STUD_STUDENT_DEGREES_MINORS', array('STUDENT_MINOR_ID', 'STUDENT_DEGREE_ID', 'MINOR_ID'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetchAll();
    
    $concentrations = $this->db()->db_select('STUD_STUDENT_DEGREES_CONCENTRATIONS')
      ->fields('STUD_STUDENT_DEGREES_CONCENTRATIONS', array('STUDENT_CONCENTRATION_ID', 'STUDENT_DEGREE_ID', 'CONCENTRATION_ID'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetchAll();

    $areas = $this->db()->db_select('STUD_STUDENT_DEGREES_AREAS')
      ->fields('STUD_STUDENT_DEGREES_AREAS', array('STUDENT_AREA_ID', 'STUDENT_DEGREE_ID', 'AREA_ID'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreStudentDegree:degrees_detail.html.twig', array('student_degree_id' => $sub_id, 'degree' => $degree, 'majors' => $majors, 'minors' => $minors, 'concentrations' => $concentrations, 'areas' => $areas));    
  }
  
  public function degreeAuditAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    
    $degree_audit = array();
    $degrees = '';
    $areas = '';
    
    $total_needed = 0;
    $total_completed = 0;
    $total_remaining = 0;
    
    if ($this->record->getSelectedRecordID()) {
      $degree_audit_service = $this->get('kula.HEd.grading.degreeaudit');
      $degree_audit = $degree_audit_service->getDegreeAuditForStudentStatus($this->record->getSelectedRecordID());
      $degrees = (count($degree_audit_service->getDegrees()) > 0) ? implode(', ', $degree_audit_service->getDegrees()) : '';
      $areas = (count($degree_audit_service->getAreas()) > 0) ? implode(', ', $degree_audit_service->getAreas()) : '';
      $total_needed = $degree_audit_service->getTotalDegreeNeeded();
      $total_completed = $degree_audit_service->getTotalDegreeCompleted();
      $total_remaining = $degree_audit_service->getTotalDegreeRemaining();
    }
    
    return $this->render('KulaHEdGradingBundle:CoreStudentDegree:degree_audit.html.twig', array('degree_audit' => $degree_audit, 'degrees' => $degrees, 'areas' => $areas, 'total_needed' => $total_needed, 'total_completed' => $total_completed, 'total_remaining' => $total_remaining));
  }
  
}