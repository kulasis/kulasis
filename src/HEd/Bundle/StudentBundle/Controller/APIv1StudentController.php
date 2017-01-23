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

  public function getStudentAction($student_id, $org = null, $term = null) {

    $student = array();

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $student = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'PERMANENT_NUMBER'))
      ->join('STUD_STUDENT', 'stu', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('stu', array('PARENT_GUARDIAN'))
      ->condition('cons.CONSTITUENT_ID', $student_id)
      ->execute()->fetch();

    // Get emergency contacts/drivers
    $student['emergency'] = $this->db()->db_select('STUD_STUDENT_EMERGENCY_CONTACT', 'emerg')
      ->fields('emerg')
      ->condition('emerg.STUDENT_ID', $student_id)
      ->condition('emerg.REMOVED', 0)
      ->execute()->fetchAll();

    if ($org AND $term) {

      // Get student status data
      $student += $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'GRADE', 'LEVEL', 'STATUS', 'ENTER_DATE', 'ENTER_CODE', 'GROUP_WITH', 'OFF_CAMPUS', 'SHIRT_SIZE', 'MED_FOOD_ALLERGIES', 'MED_ALLERGIES', 'MED_LIMITATIONS', 'MED_MEDICATIONS', 'MED_BEHAVORIAL', 'MED_MEN_EMO_SOC_HEALTH', 'MED_INSURANCE', 'MED_PHYSICIAN', 'SCHOOL', 'COMMENTS', 'ORGANIZATION_TERM_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->condition('stustatus.STUDENT_ID', $student_id)
        ->condition('org.ORGANIZATION_ABBREVIATION', $org)
        ->condition('term.TERM_ABBREVIATION', $term)
        ->execute()->fetch();

      // Get forms
      $student['agreements'] = array();
      $a = 0;
      $forms_result = $this->db()->db_select('STUD_FORM', 'form')
        ->fields('form', array('FORM_ID', 'FORM_NAME', 'FORM_TYPE', 'OPTIONAL', 'RULE', 'FORM_TEXT'))
        ->leftJoin('STUD_STUDENT_FORMS', 'stuform', 'stuform.FORM_ID = form.FORM_ID AND stuform.STUDENT_STATUS_ID = '.$student['STUDENT_STATUS_ID'])
        ->fields('stuform', array('STUDENT_FORM_ID', 'FORM_TEXT', 'AGREE', 'COMPLETED', 'COMPLETED_TIMESTAMP', 'COMPLETED_CONSTITUENT_ID', 'COMPLETED_IP'))
        ->condition('form.ORGANIZATION_TERM_ID', $student['ORGANIZATION_TERM_ID'])
        ->execute();
      while ($form_row = $forms_result->fetch()) {

        $student['agreements'][$a] = [
          'FORM_ID' => $form_row['FORM_ID'],
          'FORM_NAME' => $form_row['FORM_NAME'],
          'FORM_TEXT' => $form_row['FORM_TEXT'],
          'OPTIONAL' => $form_row['OPTIONAL']
        ];

        if ($form_row['FORM_TYPE'] == 'AGREE') {
          $student['agreements'][$a]['options']['AGREE'] = 'I agree.';
        }

        if ($form_row['OPTIONAL']) {
          $student['agreements'][$a]['options']['DISAGREE'] = 'I disagree.';
        }

        $student['agreements'][$a]['STUDENT_FORM_ID'] = $form_row['STUDENT_FORM_ID'];
        $student['agreements'][$a]['AGREE'] = $form_row['AGREE'];
        $student['agreements'][$a]['FORM_TEXT'] = $form_row['FORM_TEXT'];
        $student['agreements'][$a]['COMPLETED'] = $form_row['COMPLETED'];
        $student['agreements'][$a]['COMPLETED_TIMESTAMP'] = $form_row['COMPLETED_TIMESTAMP'];

        $a++;
      }

    }

    if (count($student)) {
      return $this->JSONResponse($student);
    } else {
      throw new NotFoundHttpException('Student not found.');
    }

  }

  public function addEmergencyContactAction($student_id) {

    $this->authorizeConstituent($student_id);

    $currentUser = $this->authorizeUser();

    $transaction = $this->db()->db_transaction();

    $emergency_contact_data = $this->form('add', 'HEd.Student.EmergencyContact', 0);
    $emergency_contact_data['HEd.Student.EmergencyContact.StudentID'] = $student_id;
    // create constituent relationship
    $changes = $this->newPoster()->add('HEd.Student.EmergencyContact', 0, $emergency_contact_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

    if ($changes) {
      $transaction->commit();
      return $this->JSONResponse($changes);
    } else {
      $transaction->rollback();
      throw new DisplayException('Emergency contact not created.');
    }

  }

  public function editEmergencyContactAction($student_id, $emergency_contact_id) {

    $this->authorizeConstituent($student_id);

    $currentUser = $this->authorizeUser();

    $transaction = $this->db()->db_transaction();

    $emergency_contact_data = $this->form('edit', 'HEd.Student.EmergencyContact', 0);

    // make sure emergency contact exists for student
    $emergency_contact = $this->db()->db_select('STUD_STUDENT_EMERGENCY_CONTACT', 'emerg')
      ->fields('emerg', array('EMERGENCY_CONTACT_ID'))
      ->condition('emerg.STUDENT_ID', $student_id)
      ->condition('emerg.EMERGENCY_CONTACT_ID', $emergency_contact_id)
      ->execute()->fetch();

    if ($emergency_contact['EMERGENCY_CONTACT_ID']) {
      // create constituent relationship
      $changes = $this->newPoster()->edit('HEd.Student.EmergencyContact', $emergency_contact['EMERGENCY_CONTACT_ID'], $emergency_contact_data
      )->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

      if ($changes) {
        $transaction->commit();
        return $this->JSONResponse($changes);
      } else {
        $transaction->rollback();
        throw new DisplayException('Emergency contact not updated.');
      }

    } // end if on emergency contact

  }

  public function deleteEmergencyContactAction($student_id, $emergency_contact_id) {

    $this->authorizeConstituent($student_id);

    $currentUser = $this->authorizeUser();

    $transaction = $this->db()->db_transaction();

    // make sure emergency contact exists for student
    $emergency_contact = $this->db()->db_select('STUD_STUDENT_EMERGENCY_CONTACT', 'emerg')
      ->fields('emerg', array('EMERGENCY_CONTACT_ID'))
      ->condition('emerg.STUDENT_ID', $student_id)
      ->condition('emerg.EMERGENCY_CONTACT_ID', $emergency_contact_id)
      ->execute()->fetch();

    if ($emergency_contact['EMERGENCY_CONTACT_ID']) {
      // create constituent relationship
      $changes = $this->newPoster()->edit('HEd.Student.EmergencyContact', $emergency_contact['EMERGENCY_CONTACT_ID'], array(
        'HEd.Student.EmergencyContact.Removed' => 1,
        'HEd.Student.EmergencyContact.RemovedTimestamp' => date('Y-m-d H:i:s'),
        'HEd.Student.EmergencyContact.RemovedUserstamp' => $currentUser
      ))->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

      if ($changes) {
        $transaction->commit();
        return $this->JSONResponse($changes);
      } else {
        $transaction->rollback();
        throw new DisplayException('Emergency contact not removed.');
      }

    } // end if on emergency contact

  }

  public function makeAgreementAction($student_id, $org, $term, $form_id) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $currentUser = $this->authorizeUser();
    $changes = null;

    $agreement_data = $this->form('add', 'HEd.Student.Form', 0);

    // See if agreement exists
    $agreement = $this->db()->db_select('STUD_STUDENT_FORMS', 'stuforms')
      ->fields('form', array('STUDENT_FORM_ID', 'FORM_NAME', 'FORM_TYPE', 'OPTIONAL', 'RULE', 'FORM_TEXT'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterm.ORGANIZATION_TERM_ID = stuforms.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->execute()->fetch();

    // Student Status Info
    $student_status_id = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->execute()->fetch()['STUDENT_STATUS_ID'];    

    // edit existing agreement
    if ($agreement['STUDENT_FORM_ID']) {

      $changes = $this->newPoster()->edit('HEd.Student.Form', $agreement['STUDENT_FORM_ID'], array(
        'HEd.Student.Form.Agree' => $agreement_data['HEd.Student.Form.Agree'],
        'HEd.Student.Form.Completed' => 1,
        'HEd.Student.Form.CompletedTimestamp' => date('Y-m-d H:i:s'),
        'HEd.Student.Form.CompletedConstituentID' => $currentUser,
        'HEd.Student.Form.CompletedIP' => $agreement_data['HEd.Student.Form.CompletedIP']
      ))->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();


    } else { // end if student_form_id
      // get agreement info
      $agreement_info = $this->db()->db_select('STUD_FORM', 'form')
        ->fields('form')
        ->condition('form.FORM_ID', $form_id)
        ->execute()->fetch();

      // add agreement
      $changes = $this->newPoster()->add('HEd.Student.Form', 0, array(
        'HEd.Student.Form.StudentStatusID' => $student_status_id,
        'HEd.Student.Form.FormID' => $form_id,
        'HEd.Student.Form.FormText' => $agreement_info['FORM_TEXT'],
        'HEd.Student.Form.Agree' => $agreement_data['HEd.Student.Form.Agree'],
        'HEd.Student.Form.Completed' => 1,
        'HEd.Student.Form.CompletedTimestamp' => date('Y-m-d H:i:s'),
        'HEd.Student.Form.CompletedConstituentID' => $currentUser,
        'HEd.Student.Form.CompletedIP' => $agreement_data['HEd.Student.Form.CompletedIP']
      ))->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();

    } // 

    if ($changes) {
      $transaction->commit();
      return $this->JSONResponse($changes);
    } else {
      $transaction->rollback();
      throw new DisplayException('No changes made.');
    }
  }

  public function updateStudentAction($student_id) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $constituent_data = $this->form('edit', 'Core.Constituent', 0);
    $student_data = $this->form('edit', 'HEd.Student', 0);

    $transaction = $this->db()->db_transaction();

    $changes = $this->newPoster()->edit('Core.Constituent', $student_id, $constituent_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();
    $changes += $this->newPoster()->edit('HEd.Student', $student_id, $student_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();
    
    if ($changes) {
      $transaction->commit();
      return $this->JSONResponse($changes);
    } else {
      $transaction->rollback();
      throw new DisplayException('No changes made.');
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