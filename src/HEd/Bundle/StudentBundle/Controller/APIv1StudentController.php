<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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

    $changes = $this->newPoster()->edit('Core.Constituent', $student_id, $constituent_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
    $changes += $this->newPoster()->edit('HEd.Student', $student_id, $student_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
    
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

    $transaction = $this->db()->db_transaction('update_child');

    $changes = null;

    if ($student_status_id) {
      $changes = $this->newPoster()->edit('HEd.Student.Status', $student_status_id, $status_data)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
    }
    
    if ($changes) {
      $transaction->commit();
      return $this->JSONResponse($changes);
    } else {
      $transaction->rollback();
    }

  }

}