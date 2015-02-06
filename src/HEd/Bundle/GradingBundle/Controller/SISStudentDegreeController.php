<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISStudentDegreeController extends Controller {
  
  public function degreesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $degrees = array();
    
    if ($this->record->getSelectedRecordID()) {
    $degrees = $this->db()->db_select('STUD_STUDENT_DEGREES')
      ->fields('STUD_STUDENT_DEGREES', array('STUDENT_DEGREE_ID', 'EFFECTIVE_DATE', 'DEGREE_ID', 'DEGREE_AWARDED', 'EXPECTED_COMPLETION_TERM_ID', 'GRADUATION_DATE', 'CONFERRED_DATE'))
      ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();    
    }

    return $this->render('KulaHEdGradingBundle:SISStudentDegree:degrees.html.twig', array('degrees' => $degrees));  
  }
  
  public function detailAction($id, $sub_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
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
    
    return $this->render('KulaHEdGradingBundle:SISStudentDegree:degrees_detail.html.twig', array('student_degree_id' => $sub_id, 'degree' => $degree, 'majors' => $majors, 'minors' => $minors, 'concentrations' => $concentrations));    
  }
  
}