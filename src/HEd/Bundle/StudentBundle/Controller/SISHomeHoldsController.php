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
      
      $this->db()->beginTransaction();
      
      foreach($items_to_void as $table => $table_row) {
        foreach($table_row as $row_id => $row) {
          $void_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_HOLDS' => array($row_id => array('VOIDED' => array('checkbox' => 'Y', 'checkbox_hidden' => ''), 'VOIDED_TIMESTAMP' => date('Y-m-d H:i:s'), 'VOIDED_USERSTAMP' => $this->session->get('user_id')))));
          unset($data);
        }
      }
      
      $this->db()->commit();
    } 
    
    $holds = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $holds = $this->db()->select('STUD_STUDENT_HOLDS', 'stuholds')
      ->fields('stuholds', array('STUDENT_HOLD_ID', 'HOLD_ID', 'HOLD_DATE', 'COMMENTS', 'VOIDED_REASON'))
      ->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'), 'constituent.CONSTITUENT_ID = stuholds.STUDENT_ID')
      ->predicate('stuholds.VOIDED', 'N')
      ->predicate('stuholds.HOLD_ID', $this->record->getSelectedRecordID())
      ->order_by('LAST_NAME', 'ASC', 'constituent')
      ->order_by('FIRST_NAME', 'ASC', 'constituent')
      ->order_by('PERMANENT_NUMBER', 'ASC', 'constituent')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:HomeHolds:index.html.twig', array('holds' => $holds));
  }
  
}