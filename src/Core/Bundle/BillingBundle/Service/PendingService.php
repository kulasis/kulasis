<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

class PendingService {
  
  protected $database;
  
  protected $poster_factory;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory) {
    $this->database = $db;
    $this->posterFactory = $poster_factory;
  }

  public function setDBOptions($options = array()) {
    $this->db_options = $options;
  }

  public function calculatePendingCharges($student_id) {
    $this->data = array();
    $i = 0;
    $this->total_amount = 0;
    $this->charges = array();

    // return class list
    $class_list_result = $this->database->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->fields('stustatus', array('STUDENT_ID'))
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = classes.SECTION_ID')
      ->fields('sec', array('SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = sec.COURSE_ID')
      ->fields('course', array('COURSE_TITLE"'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('classes.DROPPED', 0)
      ->condition('classes.START_DATE', date('Y-m-d'), '>=')
      ->execute();
    while ($class_list_row = $class_list_result->fetch()) {

      $this->data[$i] = $class_list_row;

      // Get charges and payments for class not posted
      $trans_result = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
        ->condition('trans.POSTED', 0)
        ->condition('trans.CONSTITUENT_ID', $class_list_row['STUDENT_ID'])
        ->condition('trans.STUDENT_CLASS_ID', $class_list_row['STUDENT_CLASS_ID'])
        ->execute();
      while ($trans_row = $trans_result->fetch()) {

        $this->data[$i]['billing'][] = $trans_row;
        $this->charges[] = $trans_row;
        $this->total_amount += $trans_row['AMOUNT'];

      } // end while on loop through transactions

      $i++;
    } // end while on loop through classes
  }

  public function getPendingCharges() {
    return $this->charges;
  }

  public function totalAmount() {
    return $this->total_amount;
  }
}