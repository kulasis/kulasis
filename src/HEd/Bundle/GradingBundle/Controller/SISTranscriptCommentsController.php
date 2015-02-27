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
    $comments = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY_TERMS')
      ->fields('STUD_STUDENT_COURSE_HISTORY_TERMS', array('STUDENT_COURSE_HISTORY_TERM_ID', 'STUDENT_ID', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'LEVEL', 'COMMENTS'))
      ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    }

    return $this->render('KulaHEdGradingBundle:SISTranscriptComments:comments.html.twig', array('comments' => $comments));  
  }
  
}