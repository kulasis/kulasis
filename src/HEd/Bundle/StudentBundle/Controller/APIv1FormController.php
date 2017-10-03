<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kula\Core\Bundle\FrameworkBundle\Exception\DisplayException;

class APIv1FormController extends APIController {

  public function getAgreementsAction() {

    $currentUser = $this->authorizeUser();

    // get all related constituents
    $related_constituents = array();
    $related_constituent_results = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $currentUser)
      ->execute();
    while ($related_constituent_row = $related_constituent_results->fetch()) {
      $related_constituents[] = $related_constituent_row['CONSTITUENT_ID'];
    }

    // find pending students
    $pending_constituents = array();
    $pending_results = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->fields('stustatus', array('STUDENT_ID'))
      ->condition('stustatus.STUDENT_ID', $related_constituents, 'IN')
      ->condition('classes.DROPPED', 0)
      ->condition('classes.START_DATE', date('Y-m-d'), '>=')
      ->condition('classes.REGISTRATION_TYPE', 'ONL')
      ->execute();
    while ($pending_row = $pending_results->fetch()) {
      $pending_constituents[] = $pending_row['STUDENT_ID'];
    }

    $data = array();
    // find enrollments for related constituents
    $forms_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('stustatus', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stustatus.STUDENT_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->join('STUD_FORM', 'form', 'orgterm.ORGANIZATION_TERM_ID = form.ORGANIZATION_TERM_ID')
      ->fields('form', array('FORM_ID', 'FORM_TEXT', 'OPTIONAL', 'FORM_NAME'))
      ->leftJoin('STUD_STUDENT_FORMS', 'stuforms', 'stuforms.FORM_ID = form.FORM_ID AND stuforms.STUDENT_STATUS_ID = stustatus.STUDENT_STATUS_ID AND stuforms.COMPLETED = 1')
      ->fields('stuforms', array('AGREE', 'COMPLETED', 'COMPLETED_TIMESTAMP'))
      ->condition('class.DROPPED', 0)
      ->condition('stustatus.STUDENT_ID', $pending_constituents, 'IN')
      ->orderBy('LAST_NAME', 'ASC', 'cons')
      ->orderBy('FIRST_NAME', 'ASC', 'cons')
      ->execute();
    $i = 0;
    while ($forms_row = $forms_result->fetch()) {

      $data[$i]['STUDENT_ID'] = $forms_row['STUDENT_ID'];
      $data[$i]['ORGANIZATION_ABBREVIATION'] = $forms_row['ORGANIZATION_ABBREVIATION'];
      $data[$i]['TERM_ABBREVIATION'] = $forms_row['TERM_ABBREVIATION'];
      $data[$i]['FORM_ID'] = $forms_row['FORM_ID'];
      $data[$i]['FORM_TEXT'] = $forms_row['FORM_TEXT'];
      $data[$i]['OPTIONAL'] = $forms_row['OPTIONAL'];
      $data[$i]['FORM_NAME'] = $forms_row['FORM_NAME'];
      $data[$i]['AGREE'] = $forms_row['AGREE'];
      $data[$i]['COMPLETED'] = $forms_row['COMPLETED'];
      $data[$i]['COMPLETED_TIMESTAMP'] = $forms_row['COMPLETED_TIMESTAMP'];

    $i++;
    }

    return $this->JSONResponse($data);
  }

  public function getPendingAgreementsAction() {

    $currentUser = $this->authorizeUser();
    $data = array();

    // get all related constituents
    $related_constituents = array();
    $related_constituent_results = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $currentUser)
      ->execute();
    while ($related_constituent_row = $related_constituent_results->fetch()) {
      $related_constituents[] = $related_constituent_row['CONSTITUENT_ID'];
    }

    if (count($related_constituents) > 0) {
      // find pending students
      $pending_constituents = array();
      $pending_results = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
        ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
        ->fields('stustatus', array('STUDENT_ID'))
        ->condition('stustatus.STUDENT_ID', $related_constituents, 'IN')
        ->condition('classes.DROPPED', 0)
        ->condition('classes.START_DATE', date('Y-m-d'), '>=')
        ->condition('classes.REGISTRATION_TYPE', 'ONL')
        ->execute();
      while ($pending_row = $pending_results->fetch()) {
        $pending_constituents[] = $pending_row['STUDENT_ID'];
      }

      if (count($pending_constituents) > 0) {
        // find enrollments for related constituents
        $forms_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
          ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
          ->fields('stustatus', array('STUDENT_ID'))
          ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stustatus.STUDENT_ID')
          ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
          ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
          ->fields('org', array('ORGANIZATION_ABBREVIATION'))
          ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
          ->fields('term', array('TERM_ABBREVIATION'))
          ->join('STUD_FORM', 'form', 'orgterm.ORGANIZATION_TERM_ID = form.ORGANIZATION_TERM_ID')
          ->fields('form', array('FORM_ID', 'FORM_TEXT', 'OPTIONAL', 'FORM_NAME'))
          ->leftJoin('STUD_STUDENT_FORMS', 'stuforms', 'stuforms.FORM_ID = form.FORM_ID AND stuforms.STUDENT_STATUS_ID = stustatus.STUDENT_STATUS_ID AND stuforms.COMPLETED = 1')
          ->fields('stuforms', array('AGREE', 'COMPLETED', 'COMPLETED_TIMESTAMP'))
          ->condition('class.DROPPED', 0)
          ->condition('stustatus.STUDENT_ID', $pending_constituents, 'IN')
          ->isNull('stuforms.STUDENT_FORM_ID')
          ->orderBy('LAST_NAME', 'ASC', 'cons')
          ->orderBy('FIRST_NAME', 'ASC', 'cons')
          ->execute();
        $i = 0;
        while ($forms_row = $forms_result->fetch()) {

          $data[$i]['STUDENT_ID'] = $forms_row['STUDENT_ID'];
          $data[$i]['ORGANIZATION_ABBREVIATION'] = $forms_row['ORGANIZATION_ABBREVIATION'];
          $data[$i]['TERM_ABBREVIATION'] = $forms_row['TERM_ABBREVIATION'];
          $data[$i]['FORM_ID'] = $forms_row['FORM_ID'];
          $data[$i]['FORM_TEXT'] = $forms_row['FORM_TEXT'];
          $data[$i]['OPTIONAL'] = $forms_row['OPTIONAL'];
          $data[$i]['FORM_NAME'] = $forms_row['FORM_NAME'];
          $data[$i]['AGREE'] = $forms_row['AGREE'];
          $data[$i]['COMPLETED'] = $forms_row['COMPLETED'];
          $data[$i]['COMPLETED_TIMESTAMP'] = $forms_row['COMPLETED_TIMESTAMP'];

        $i++;
        }
      // end if more than zero pending constituents
    } // end if more than zero related constituents

    return $this->JSONResponse($data);
  }

  public function makeAgreementAction($student_id, $org, $term, $form_id) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $currentUser = $this->authorizeUser();
    $changes = null;

    $agreement_data = $this->form('add', 'HEd.Student.Form', 0);

    // Student Status Info
    $student_status_id = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->execute()->fetch()['STUDENT_STATUS_ID']; 

    // See if agreement exists
    $agreement = $this->db()->db_select('STUD_STUDENT_FORMS', 'forms')
      ->fields('forms', array('STUDENT_FORM_ID'))
      ->join('STUD_FORM', 'form', 'form.FORM_ID = forms.FORM_ID')
      ->fields('form', array('FORM_NAME', 'FORM_TYPE', 'OPTIONAL', 'RULE', 'FORM_TEXT'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = form.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->condition('forms.STUDENT_STATUS_ID', $student_status_id)
      ->condition('forms.FORM_ID', $form_id)
      ->execute()->fetch();   

    // edit existing agreement
    if ($agreement['STUDENT_FORM_ID']) {

      $changes = $this->newPoster()->edit('HEd.Student.Form', $agreement['STUDENT_FORM_ID'], array(
        'HEd.Student.Form.Agree' => $agreement_data['HEd.Student.Form.Agree'],
        'HEd.Student.Form.Completed' => 1,
        'HEd.Student.Form.CompletedTimestamp' => date('Y-m-d H:i:s'),
        'HEd.Student.Form.CompletedConstituentID' => $currentUser,
        'HEd.Student.Form.CompletedIP' => $agreement_data['HEd.Student.Form.CompletedIP']
      ))->process(array('VERIFY_PERMISSIONS' => false))->getResult();


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
      ))->process(array('VERIFY_PERMISSIONS' => false))->getResult();

    } // 

    if ($changes) {
      return $this->JSONResponse($changes);
    } else {
      $transaction->rollback();
      throw new DisplayException('No changes made.');
    }
  }

}