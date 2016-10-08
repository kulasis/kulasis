<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1OrganizationController extends APIController {

  public function schoolsAction() {
    $this->authorize();

    $schools = array();
    $schoolsResult = $this->db()->db_select('CORE_ORGANIZATION', 'org')
      ->fields('org')
      ->condition('org.ORGANIZATION_TYPE', 'S')
      ->condition('org.ACTIVE', 1)
      ->orderBy('ORGANIZATION_NAME', 'org')
      ->execute();
    while ($schoolsRow = $schoolsResult->fetch()) {
      $schools[] = array(
        'id' => $schoolsRow['ORGANIZATION_ID'],
        'name' => $schoolsRow['ORGANIZATION_NAME'],
        'abbreviation' => $schoolsRow['ORGANIZATION_ABBREVIATION']
      );
    }

    return $this->JSONResponse($schools);
  }

}