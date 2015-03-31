<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISHomeHoldsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Hold');
    
    if ($this->request->request->get('post')) {
      
      $items_to_void = $this->request->request->get('post');
      
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
    } 
    
    $holds = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $holds = $this->db()->db_select('STUD_STUDENT_HOLDS', 'stuholds')
      ->fields('stuholds', array('STUDENT_HOLD_ID', 'HOLD_ID', 'HOLD_DATE', 'COMMENTS', 'VOIDED_REASON'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = stuholds.STUDENT_ID')
      ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
      ->condition('stuholds.VOIDED', 0)
      ->condition('stuholds.HOLD_ID', $this->record->getSelectedRecordID())
      ->orderBy('constituent.LAST_NAME', 'ASC')
      ->orderBy('constituent.FIRST_NAME', 'ASC')
      ->orderBy('constituent.PERMANENT_NUMBER', 'ASC')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:SISHomeHolds:index.html.twig', array('holds' => $holds));
  }
  
}