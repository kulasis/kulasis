<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1UserController extends APIController {

  public function currentUserAction() {
    $currentUser = $this->authorizeUser();

    return $this->getUserViaAPI($currentUser);
  }

  public function userAction($user_id) {
    $this->authorize();

    return $this->getUserViaAPI($user_id);
  }

  private function getUserViaAPI($user_id) {

    $user = array();
    $userResult = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
      ->fields('cons', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->condition('cons.CONSTITUENT_ID', $user_id)
      ->execute();
    while ($userRow = $userResult->fetch()) {
      $user = array(
        'id' => $userRow['CONSTITUENT_ID'],
        'last_name' => $userRow['LAST_NAME'],
        'first_name' => $userRow['FIRST_NAME'],
        'permanent_number' => $userRow['PERMANENT_NUMBER']
      );
    }

    return $this->JSONResponse($user);
  }

}