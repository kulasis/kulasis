<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISStandingsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('STUDENT');
    
    $standings = array();
    
    if ($this->record->getSelectedRecordID()) {
    $standings = $this->db()->select('STUD_STUDENT_COURSE_HISTORY_STANDING', 'chstandings')
      ->fields('chstandings', array('STUDENT_COURSE_HISTORY_STANDING_ID', 'STUDENT_ID', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'STANDING_ID'))
      ->join('STUD_STANDING', 'standing', array('STANDING_DESCRIPTION'), 'standing.STANDING_ID = chstandings.STANDING_ID')
      ->predicate('STUDENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();    
    }

    return $this->render('KulaHEdCourseHistoryBundle:Standings:standings.html.twig', array('standings' => $standings));  
  }
  
}