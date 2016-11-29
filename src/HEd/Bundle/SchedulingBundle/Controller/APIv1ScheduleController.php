<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class APIv1ScheduleController extends APIController {

  public function addClassAction($student_id, $section_id) {

    $schedule = null;

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    // Get organization term from section
    $section = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'ORGANIZATION_TERM_ID', 'OPEN_REGISTRATION', 'CLOSE_REGISTRATION', 'ALLOW_REGISTRATION', 'CAPACITY'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID'))
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ID'))
      ->condition('sec.SECTION_ID', $section_id)
      ->execute()->fetch();
    if ($section['SECTION_ID'] == '') {
      throw new NotFoundHttpException('Section does not exist.');
    }
    if ($section['ALLOW_REGISTRATION'] == 0) {
      throw new NotFoundHttpException('Section does not allow registration.');
    }
    if (date('Y-m-d') < $section['OPEN_REGISTRATION'] OR date('Y-m-d') > $section['CLOSE_REGISTRATION']) {
      throw new NotFoundHttpException('Section closed from registration.');
    }

    // Find student; if doesn't exist, create student record
    $student = $this->db()->db_select('STUD_STUDENT', 'stu')
      ->fields('stu', array('STUDENT_ID'))
      ->condition('stu.STUDENT_ID', $student_id)
      ->execute()->fetch();

    // Student does not exist, create them.
    if ($student['STUDENT_ID'] == '') {

      // Get defaults
      $defaults = $this->db()->db_select('STUD_SCHOOL', 'sch')
        ->fields('sch')
        ->condition('sch.SCHOOL_ID', $section['ORGANIZATION_ID'])
        ->execute()->fetch();

       $student_id = $this->get('kula.HEd.student')->addStudent($student_id, null,
      array(
         'HEd.Student.Status.EnterDate' => date('Y-m-d'),
         'HEd.Student.Status.EnterCode' => $defaults['DEFAULT_ENTER_CODE'],
         'HEd.Student.Status.EnterTerm' => $section['TERM_ID'],
         'HEd.Student.Status.Resident' => 'C'
      ), array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false)
      );
       
    } else {
      $student_id = $student['STUDENT_ID'];
    }
    
    // Get student status record; if doesn't exist, create it.  Determine Organization Term based on section.
    $student_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('stustatus.ORGANIZATION_TERM_ID', $section['ORGANIZATION_TERM_ID'])
      ->execute()->fetch();

    // Get defaults
      $defaults = $this->db()->db_select('STUD_SCHOOL', 'sch')
        ->fields('sch')
        ->condition('sch.SCHOOL_ID', $section['ORGANIZATION_ID'])
        ->execute()->fetch();

    // Student status does not exist, enroll student
    if ($student_status['STUDENT_STATUS_ID'] == '') {
      $student_enrollment = $this->get('kula.HEd.student')->enrollStudent(array(
        'StudentID' => $student_id,
        'OrganizationTermID' => $section['ORGANIZATION_TERM_ID'],
        'HEd.Student.Status.Grade' => $defaults['DEFAULT_GRADE'],
        'HEd.Student.Status.Level' => $defaults['DEFAULT_LEVEL'],
        'HEd.Student.Status.EnterDate' => date('Y-m-d'),
        'HEd.Student.Status.EnterCode' => $defaults['DEFAULT_ENTER_CODE'],
        'HEd.Student.Status.Resident' => 'C'
      ), array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

      $student_status_id = $student_enrollment['student_status'];
    } else {
      $student_status_id = $student_status['STUDENT_STATUS_ID'];
    }

    // Calculate tuition rate
    $this->get('kula.HEd.billing.constituent')->determineTuitionRate($student_status_id, array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

    // Make sure class not full
    $transaction = $this->db()->db_transaction();

    $class_count = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->expression('COUNT(*)', 'class_total')
      ->condition('class.SECTION_ID', $section_id)
      ->condition('class.DROPPED', 0)
      ->execute()
      ->fetch();
    if ($class_count['class_total'] <= $section['CAPACITY']) {
      $schedule = $this->get('kula.HEd.scheduling.schedule')->addClassForStudentStatus($student_status_id, $section_id, date('Y-m-d'), 0, array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

      // Add discount
      $discount_id = $this->request->request->get('discount_id');
      if ($discount_id != '') {
        $discount_or = $this->db()->db_or();
        $discount_or = $discount_or->condition('disc.END_DATE', date('Y-m-d'), '>=');
        $discount_or = $discount_or->isNull('disc.END_DATE');
        
        // Get discount code
        $discount_info = $this->db()->db_select('BILL_SECTION_FEE_DISCOUNT', 'disc')
          ->fields('disc', array('SECTION_ID', 'CODE_ID', 'AMOUNT'))
          ->condition('disc.SECTION_FEE_DISCOUNT_ID', $discount_id)
          ->condition($discount_or)
          ->execute()->fetch();

        $payment_service = $this->get('kula.Core.billing.payment');
        $payment_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
        $payment_id = $payment_service->addPayment($student_id, $student_id, null, date('Y-m-d'), null, $discount_info['AMOUNT']);

        $transaction_service = $this->get('kula.Core.billing.transaction');
        $transaction_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
        $transaction_service->addDiscount($discount_id, $student_id, $section['ORGANIZATION_TERM_ID'], $schedule, $payment_id);
      }
      $transaction->commit();
    }


    // Create schedule record
    return $this->jsonResponse($schedule);

  }

  public function removeClassAction($class_id) {

    // get student ID from class ID
    $student_id = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('stustatus', array('STUDENT_ID'))
      ->condition('class.STUDENT_CLASS_ID', $class_id)
      ->execute()->fetch()['STUDENT_ID'];

    // check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $schedule_service = $this->get('kula.HEd.scheduling.schedule');
    $schedule_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

    // Remove class record
    return $this->jsonResponse($schedule_service->dropClassForStudentStatus($class_id, date('Y-m-d')));
  }

  public function getClassesAction($student_id, $org = null, $term = null) {
    // check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $data = array();
    $i = 0;

    $data['billing_total'] = 0;

    // return class list
    $class_list_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array())
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
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

      $data['classes'][$i] = $class_list_row;

      if ($class_list_row['SECTION_NAME']) 
        $data['classes'][$i]['SECTION_NAME'] = $class_list_row['SECTION_NAME']; 
      else 
        $data['classes'][$i]['SECTION_NAME'] = $class_list_row['COURSE_TITLE'];

      $data['classes'][$i]['billing_total']  = 0;

      // Get charges and payments for class not posted
      $trans_result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
        ->condition('trans.POSTED', 1)
        ->condition('trans.CONSTITUENT_ID', $currentUser)
        ->condition($trans_db_or)
        ->execute();
      while ($trans_row = $trans_result->fetch()) {

        $data['classes'][$i]['billing'][] = $trans_row;

        $data['classes'][$i]['billing_total'] += $trans_row['AMOUNT'];
        $data['billing_total'] += $trans_row['AMOUNT'];
      } // end while on loop through transactions

    $i++;
    }

    return $this->jsonResponse($class_list);
  }

  public function getPendingClassesAction() {

    // get logged in user
    $currentUser = $this->authorizeUser();

    $data = array();
    $i = 0;

    $data['billing_total'] = 0;

    // return class list
    $class_list_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = classes.SECTION_ID')
      ->fields('sec', array('SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = sec.COURSE_ID')
      ->fields('course', array('COURSE_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('stustatus.STUDENT_ID', $currentUser)
      ->condition('classes.DROPPED', 0)
      ->condition('classes.START_DATE', date('Y-m-d'), '>=')
      ->execute();
    while ($class_list_row = $class_list_result->fetch()) {

      $data['classes'][$i] = $class_list_row;

      if ($class_list_row['SECTION_NAME']) 
        $data['classes'][$i]['SECTION_NAME'] = $class_list_row['SECTION_NAME']; 
      else 
        $data['classes'][$i]['SECTION_NAME'] = $class_list_row['COURSE_TITLE'];

      $data['classes'][$i]['billing_total']  = 0;
      // Get charges and payments for class not posted
      $trans_result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
        ->condition('trans.POSTED', 0)
        ->condition('trans.CONSTITUENT_ID', $currentUser)
        ->condition('trans.STUDENT_CLASS_ID', $class_list_row['STUDENT_CLASS_ID'])
        ->execute();
      while ($trans_row = $trans_result->fetch()) {

        $data['classes'][$i]['billing'][] = $trans_row;

        $data['classes'][$i]['billing_total'] += $trans_row['AMOUNT'];
        $data['billing_total'] += $trans_row['AMOUNT'];
      } // end while on loop through transactions

      $i++;
    } // end while on loop through classes

    // return class list
    return $this->jsonResponse($data);
  }

}