<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISHoldsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student');
    
    if ($this->request->request->get('void')) {
      $items_to_void = $this->request->request->get('void');
      
      $transaction = $this->db()->db_transaction();
      
      foreach($items_to_void as $table => $table_row) {
        foreach($table_row as $row_id => $row) {
          $void_poster = $this->newPoster()->edit('HEd.Student.Hold', $row_id, array(
            'HEd.Student.Hold.Voided' => 1, 
            'HEd.Student.Hold.VoidedTimestamp' => date('Y-m-d H:i:s'), 
            'HEd.Student.Hold.VoidedUserstamp' => $this->session->get('user_id')
          ))->process();
        }
      }
      
      $transaction->commit();
      
    } else {
      $this->processForm();
    }
    
    $holds = array();
    
    if ($this->record->getSelectedRecordID()) {
      $holds = $this->db()->select('STUD_STUDENT_HOLDS', 'stuholds')
        ->fields('stuholds', array('STUDENT_HOLD_ID', 'HOLD_ID', 'HOLD_DATE', 'COMMENTS', 'VOIDED', 'VOIDED_REASON', 'VOIDED_TIMESTAMP'))
        ->join('STUD_HOLD', 'hold', 'stuholds.HOLD_ID = hold.HOLD_ID')
        ->fields('hold', array('HOLD_NAME'))
        ->leftJoin('CORE_USER', 'user', 'user.USER_ID = stuholds.VOIDED_USERSTAMP')
        ->fields('user', array('USERNAME'))
        ->condition('stuholds.STUDENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('HOLD_DATE', 'DESC', 'stuholds')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:SISHolds:index.html.twig', array('holds' => $holds));
  }
}