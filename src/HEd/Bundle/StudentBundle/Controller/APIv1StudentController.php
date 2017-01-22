<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kula\Core\Bundle\FrameworkBundle\Exception\DisplayException;

class APIv1StudentController extends APIController {

  public function relatedChildrenAction() {

    $currentUser = $this->authorizeUser();

    $data = array();

    $data = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $currentUser)
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = rel.CONSTITUENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->execute()->fetchAll();

    return $this->JSONResponse($data);
  }

  public function createChildAction() {
    $currentUser = $this->authorizeUser();

    $transaction = $this->db()->db_transaction('create_user');

    // create constituent
    $constituent_service = $this->get('Kula.Core.Constituent');
    $constituent_data = $this->form('add', 'Core.Constituent', 0);
    $relationship_data = $this->form('add', 'Core.Constituent.Relationship', 0);
    $constituent_id = $constituent_service->createConstituent($constituent_data);

    // create constituent relationship
    $this->newPoster()->add('Core.Constituent.Relationship', 0, array(
      'Core.Constituent.Relationship.ConstituentID' => $constituent_id,
      'Core.Constituent.Relationship.RelatedConstituentID' => $currentUser,
      'Core.Constituent.Relationship.Relationship' => isset($relationship_data['Core.Constituent.Relationship.Relationship']) ? $relationship_data['Core.Constituent.Relationship.Relationship'] : null
    ))->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

    if ($constituent_id) {
      $transaction->commit();
      return $this->JSONResponse($constituent_id);
    } else {
      $transaction->rollback();
    }

  }

  public function updateStudentAction($student_id) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $constituent_data = $this->form('edit', 'Core.Constituent', 0);
    $student_data = $this->form('edit', 'HEd.Student', 0);

    $transaction = $this->db()->db_transaction('update_child');

    $changes = $this->newPoster()->edit('Core.Constituent', $student_id, $constituent_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();
    $changes += $this->newPoster()->edit('HEd.Student', $student_id, $student_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();
    
    if ($changes) {
      $transaction->commit();
      return $this->JSONResponse($changes);
    } else {
      $transaction->rollback();
    }

  }

  public function updateStudentEnrollmentAction($student_id, $org, $term) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $status_data = $this->form('edit', 'HEd.Student.Status', 0);

    // Get student status
    $student_status_id = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->execute()->fetch()['STUDENT_STATUS_ID'];

    if ($student_status_id == '') {

      // Term info
      $term_info = $this->db()->db_select('CORE_ORGANIZATION_TERMS', 'orgterms')
        ->fields('orgterms', array('ORGANIZATION_TERM_ID'))
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ID'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ID'))
        ->condition('org.ORGANIZATION_ABBREVIATION', $org)
        ->condition('term.TERM_ABBREVIATION', $term)
        ->execute()->fetch();

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
          ->condition('SCHOOL_ID', $term_info['ORGANIZATION_ID'])
          ->execute()->fetch();

         $student_id = $this->get('kula.HEd.student')->addStudent($student_id, null,
        array(
           'HEd.Student.Status.EnterDate' => date('Y-m-d'),
           'HEd.Student.Status.EnterCode' => $defaults['DEFAULT_ENTER_CODE'],
           'HEd.Student.Status.EnterTerm' => $term_info['TERM_ID'],
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
        ->condition('stustatus.ORGANIZATION_TERM_ID', $term_info['ORGANIZATION_TERM_ID'])
        ->execute()->fetch();

      // Get defaults
        $defaults = $this->db()->db_select('STUD_SCHOOL', 'sch')
          ->fields('sch')
          ->condition('sch.SCHOOL_ID', $term_info['ORGANIZATION_ID'])
          ->execute()->fetch();

      // Student status does not exist, enroll student
      if ($student_status['STUDENT_STATUS_ID'] == '') {
        $student_enrollment = $this->get('kula.HEd.student')->enrollStudent(array(
          'StudentID' => $student_id,
          'OrganizationTermID' => $term_info['ORGANIZATION_TERM_ID'],
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

    }

    $transaction = $this->db()->db_transaction('update_child');

    $changes = null;

    if ($student_status_id) {
      $changes = $this->newPoster()->edit('HEd.Student.Status', $student_status_id, $status_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();
    }
    
    if ($changes) {
      $transaction->commit();
      return $this->JSONResponse($changes);
    } else {
      $transaction->rollback();
      throw new DisplayException('No changes made.');
    }

  }

}