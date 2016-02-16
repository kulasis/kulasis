<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreStandingsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $standings = array();
    
    if ($this->record->getSelectedRecordID()) {
    $standings = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY_STANDING', 'chstandings')
      ->fields('chstandings', array('STUDENT_COURSE_HISTORY_STANDING_ID', 'STUDENT_ID', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'STANDING_ID'))
      ->join('STUD_STANDING', 'standing', 'standing.STANDING_ID = chstandings.STANDING_ID')
      ->fields('standing', array('STANDING_DESCRIPTION'))
      ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();    
    }

    return $this->render('KulaHEdGradingBundle:CoreStandings:standings.html.twig', array('standings' => $standings));  
  }
  
}