<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISTranscriptCommentsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $comments = array();
    
    if ($this->record->getSelectedRecordID()) {
    $comments = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY_COMMENT')
      ->fields('STUD_STUDENT_COURSE_HISTORY_COMMENT', array('STUDENT_COURSE_HISTORY_COMMENT_ID', 'STUDENT_ID', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'LEVEL', 'COMMENTS'))
      ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();    
    }

    return $this->render('KulaHEdGradingBundle:SISTranscriptComments:comments.html.twig', array('comments' => $comments));  
  }
  
  public function detailAction($id, $sub_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $degree = $this->db()->select('STUD_STUDENT_DEGREES')
      ->fields('STUD_STUDENT_DEGREES', array('STUDENT_DEGREE_ID', 'DEGREE_AWARDED', 'GRADUATION_DATE', 'CONFERRED_DATE'))
      ->join('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = STUD_STUDENT_DEGREES.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetch();
    
    $majors = $this->db()->select('STUD_STUDENT_DEGREES_MAJORS')
      ->fields('STUD_STUDENT_DEGREES_MAJORS', array('STUDENT_MAJOR_ID', 'STUDENT_DEGREE_ID', 'MAJOR_ID'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetchAll();
    
    $minors = $this->db()->select('STUD_STUDENT_DEGREES_MINORS')
      ->fields('STUD_STUDENT_DEGREES_MINORS', array('STUDENT_MINOR_ID', 'STUDENT_DEGREE_ID', 'MINOR_ID'))
      ->condition('STUDENT_DEGREE_ID', $sub_id)
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:SISTranscriptComments:degrees_detail.html.twig', array('student_degree_id' => $sub_id, 'degree' => $degree, 'majors' => $majors, 'minors' => $minors));    
  }
  
}