<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kula\Core\Bundle\FrameworkBundle\Exception\DisplayException;

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
    if (time() < strtotime($section['OPEN_REGISTRATION']) OR time() > strtotime($section['CLOSE_REGISTRATION'])) {
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

      if ($schedule) {

        // Add discount
        $discount_id = $this->request->request->get('discount_id');
        $discount_proof = $this->request->request->get('discount_proof');
        if ($discount_id != '') {
          $discount_or = $this->db()->db_or();
          $discount_or = $discount_or->condition('disc.END_DATE', date('Y-m-d'), '>=');
          $discount_or = $discount_or->isNull('disc.END_DATE');
          
          // Get discount code
          $discount_info = $this->db()->db_select('BILL_SECTION_FEE_DISCOUNT', 'disc')
            ->fields('disc', array('SECTION_FEE_DISCOUNT_ID', 'SECTION_ID', 'CODE_ID', 'AMOUNT'))
            ->condition('disc.SECTION_ID', $section_id)
            ->condition('disc.SECTION_FEE_DISCOUNT_ID', $discount_id)
            ->condition($discount_or)
            ->execute()->fetch();
          if ($discount_info['SECTION_FEE_DISCOUNT_ID'] != '') {
            if ($discount_info['AMOUNT'] < 0) {
              $discount_info['AMOUNT'] = $discount_info['AMOUNT'] * -1;
            }

            if ($schedule) {
            // Add Discount payment
            $payment_service = $this->get('kula.Core.billing.payment');
            $payment_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
            $payment_id = $payment_service->addPayment($student_id, $student_id, 'D', null, date('Y-m-d'), null, $discount_info['AMOUNT'], null, $discount_proof);

            // Add discount transaction
            $transaction_service = $this->get('kula.Core.billing.transaction');
            $transaction_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
            $transaction_service->addDiscount($discount_id, $student_id, $section['ORGANIZATION_TERM_ID'], $schedule, $payment_id);

            // Get largest charge
            $charge_id = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
              ->fields('trans', array('CONSTITUENT_TRANSACTION_ID', 'APPLIED_BALANCE'))
              ->condition('trans.CONSTITUENT_ID', $student_id)
              ->condition('trans.STUDENT_CLASS_ID', $schedule)
              ->orderBy('APPLIED_BALANCE', 'DESC', 'trans')
              ->execute()->fetch();

              if ($charge_id['CONSTITUENT_TRANSACTION_ID']) {
                // Calculate applied payment
                $payment_service->addAppliedPayment($payment_id, $charge_id['CONSTITUENT_TRANSACTION_ID'], $discount_info['AMOUNT']);

                $payment_service->calculateBalanceForCharge($charge_id['CONSTITUENT_TRANSACTION_ID']);
                            $payment_service->calculateBalanceForPayment($payment_id);
              }
            } else {
              throw new DisplayException('Invalid class id sent with discount.');
            } 
          } else {
           throw new NotFoundHttpException('Invalid discount.');
          }
        } // end if on existance of discount
      } else {
        throw new DisplayException('Could not enroll in class.');
      }
      
      $transaction->commit();
    } else {
      $transaction->rollback();
      throw new DisplayException('Class is full.');
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

    // check if paid
    $paid = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
    	->fields('trans', array('CONSTITUENT_TRANSACTION_ID'))
      ->condition('trans.CONSTITUENT_ID', $student_id)
      ->condition('trans.STUDENT_CLASS_ID', $class_id)
      ->condition('trans.POSTED', 1)
      ->condition('trans.APPLIED_BALANCE', 0)
      ->condition('trans.VOIDED', 0)
      ->execute()->fetch();

    if ($paid['CONSTITUENT_TRANSACTION_ID'] != '') {
      throw new DisplayException('Cannot drop class that has already been paid.');
    }

    // Remove class record
    return $this->jsonResponse($schedule_service->dropClassForStudentStatus($class_id, date('Y-m-d')));
  }

  public function getClassesAction($student_id, $org = null, $term = null) {
    // check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $data = array();
    $i = 0;

    // return class list
    $class_list_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID', 'START_DATE', 'CREATED_TIMESTAMP'))
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
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('classes.DROPPED', 0)
      ->condition('classes.START_DATE', date('Y-m-d'), '>=')
      ->execute();
    while ($class_list_row = $class_list_result->fetch()) {

      // check if paid
      $paid = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID'))
        ->condition('trans.CONSTITUENT_ID', $student_id)
        ->condition('trans.STUDENT_CLASS_ID', $class_list_row['STUDENT_CLASS_ID'])
        ->condition('trans.POSTED', 1)
        ->condition('trans.APPLIED_BALANCE', 0)
        ->condition('trans.VOIDED', 0)
        ->execute()->fetch();

      if ($paid['CONSTITUENT_TRANSACTION_ID'] != '') {
        $data[$i] = $class_list_row;

        if ($class_list_row['SECTION_NAME']) 
          $data[$i]['SECTION_NAME'] = $class_list_row['SECTION_NAME']; 
        else 
          $data[$i]['SECTION_NAME'] = $class_list_row['COURSE_TITLE'];

      $i++;
      }

    }

    return $this->jsonResponse($data);
  }

  public function getPendingClassesAction() {
    // get logged in user
    $currentUser = $this->authorizeUser();
    $data = array();
    $pending_service = $this->get('kula.Core.billing.pending');
    $pending_service->calculatePendingCharges($currentUser);
    $data['classes'] = $pending_service->getPendingClasses();
    $data['billing_total'] = $pending_service->totalAmount();
    // return class list
    return $this->jsonResponse($data);
  }
}