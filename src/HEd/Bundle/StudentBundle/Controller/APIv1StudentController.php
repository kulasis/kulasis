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
    $i = 0;
    $data_result = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $currentUser)
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = rel.CONSTITUENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER', 'BIRTH_DATE'))
      ->execute();
    while ($data = $data_result->fetch()) {
      $student[$i] = $data;

      // Get emergency contacts/drivers
      $student[$i]['emergency'] = $this->db()->db_select('STUD_STUDENT_EMERGENCY_CONTACT', 'emerg')
        ->fields('emerg')
        ->condition('emerg.STUDENT_ID', $data['CONSTITUENT_ID'])
        ->condition('emerg.REMOVED', 0)
        ->execute()->fetchAll();

    $i++;
    }
    
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
    $relationship_id = $this->newPoster()->add('Core.Constituent.Relationship', 0, array(
      'Core.Constituent.Relationship.ConstituentID' => $constituent_id,
      'Core.Constituent.Relationship.RelatedConstituentID' => $currentUser,
      'Core.Constituent.Relationship.Relationship' => isset($relationship_data['Core.Constituent.Relationship.Relationship']) ? $relationship_data['Core.Constituent.Relationship.Relationship'] : null
    ))->process(array('VERIFY_PERMISSIONS' => false))->getID();

    // add parent record if it doesn't exist
    $existing_parent_id = $this->db()->db_select('STUD_PARENT', 'parent')
      ->fields('parent', array('PARENT_ID'))
      ->condition('parent.PARENT_ID', $currentUser)
      ->execute()->fetch();

    if (!$existing_parent_id['PARENT_ID']) {
      $parent_id = $this->newPoster()->add('HEd.Parent', 0, array(
        'HEd.Parent.ID' => $currentUser
      ))->process(array('VERIFY_PERMISSIONS' => false))->getID();      
    }

    // add student parent relationship record
    $student_parent_id = $this->newPoster()->add('HEd.Student.Parent', 0, array(
      'HEd.Student.Parent.ID' => $relationship_id
    ))->process(array('VERIFY_PERMISSIONS' => false))->getID();

    // add any contact information of user to child
    $contact_info_service = $this->get('kula.Core.ContactInfo');
    $contact_info_service->syncCurrentEmail($currentUser, $constituent_id);
    $contact_info_service->syncCurrentPhone($currentUser, $constituent_id);
    $contact_info_service->syncCurrentAddresses($currentUser, $constituent_id);

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
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'PERMANENT_NUMBER', 'GENDER', 'BIRTH_DATE'))
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
      $student_enrollment = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'LEVEL', 'STATUS', 'GRADE', 'ENTER_DATE', 'ENTER_CODE', 'GROUP_WITH', 'OFF_CAMPUS', 'SHIRT_SIZE', 'MED_FOOD_ALLERGIES', 'MED_ALLERGIES', 'MED_LIMITATIONS', 'MED_MEDICATIONS', 'MED_BEHAVIORAL', 'MED_MEN_EMO_SOC_HEALTH', 'MED_INSURANCE', 'MED_PHYSICIAN', 'SCHOOL', 'COMMENTS', 'ORGANIZATION_TERM_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->condition('stustatus.STUDENT_ID', $student_id)
        ->condition('org.ORGANIZATION_ABBREVIATION', $org)
        ->condition('term.TERM_ABBREVIATION', $term)
        ->execute()->fetch();
      $student += $student_enrollment;

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
    $changes = $this->newPoster()->add('HEd.Student.EmergencyContact', 0, $emergency_contact_data)->process(array('VERIFY_PERMISSIONS' => false));

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
      )->process(array('VERIFY_PERMISSIONS' => false));

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
      ))->process(array('VERIFY_PERMISSIONS' => false));

      if ($changes) {
        $transaction->commit();
        return $this->JSONResponse($changes);
      } else {
        $transaction->rollback();
        throw new DisplayException('Emergency contact not removed.');
      }

    } // end if on emergency contact

  }

  public function updateStudentAction($student_id) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $currentUser = $this->authorizeUser();

    $constituent_data = $this->form('edit', 'Core.Constituent', 0);
    $student_data = $this->form('edit', 'HEd.Student', 0);
    $relationship_data = $this->form('edit', 'Core.Constituent.Relationship', 0);

    $transaction = $this->db()->db_transaction();

    $changes = $this->newPoster()->edit('Core.Constituent', $student_id, $constituent_data)->process(array('VERIFY_PERMISSIONS' => false))->getResult();
    $changes += $this->newPoster()->edit('HEd.Student', $student_id, $student_data)->process(array('VERIFY_PERMISSIONS' => false))->getResult();
    
    // get constituent relationship
    $relationship = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('RELATIONSHIP_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $currentUser)
      ->condition('rel.CONSTITUENT_ID', $student_id)
      ->execute()->fetch();

    if ($relationship['RELATIONSHIP_ID']) {
      $changes += $this->newPoster()->edit('Core.Constituent.Relationship', $relationship['RELATIONSHIP_ID'], $relationship_data)->process(array('VERIFY_PERMISSIONS' => false))->getResult();
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