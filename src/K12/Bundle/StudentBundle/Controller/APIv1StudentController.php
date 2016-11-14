<?php

namespace Kula\K12\Bundle\StudentBundle\Controller;

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

}