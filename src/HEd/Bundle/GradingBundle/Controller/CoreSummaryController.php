<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSummaryController extends Controller {
  
  public function termsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $gpa = array();
    
    if ($this->record->getSelectedRecordID()) {
    $gpa = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY_TERMS', 'stugpa')
      ->fields('stugpa')
      ->condition('stugpa.STUDENT_ID', $this->record->getSelectedRecordID())
      ->orderBy('LEVEL', 'ASC')
      ->orderBy('CALENDAR_YEAR', 'ASC')
      ->orderBy('CALENDAR_MONTH', 'ASC')
      ->execute()->fetchAll();    
    }

    return $this->render('KulaHEdGradingBundle:CoreSummary:terms.html.twig', array('terms' => $gpa));  
  }
  
  public function calculateTermTotalsAction() {
    $this->authorize();
    $this->formNewWindow();
    $this->formAction('sis_HEd_student_coursehistory_calculateTermTotals_calculate');
    
    if ($this->request->query->get('record_type') == 'Core.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student');
    return $this->render('KulaHEdGradingBundle:CoreSummary:action_calculateTermTotals.html.twig');
  }
  
  public function performCalculateTermTotalsAction() {
    $this->authorize();
    
    $termTotalsService = $this->get('kula.HEd.grading.termtotals');
    
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $termTotalsService->calculateTermTotals($record_id);
    else {
      
      $students = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_ID'))
        ->condition('stustatus.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->execute();
      while ($student = $students->fetch()) {
        $termTotalsService->calculateTermTotals($student['STUDENT_ID']);
      }
    }
    
    return $this->textResponse("Completed.");
  }
  
}