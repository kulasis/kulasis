<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1OrganizationTermsController extends APIController {

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

  public function schoolTermsAction($school_abbreviation) {
    $this->authorize();

    $terms = array();
    $termsResult = $this->db()->db_select('CORE_ORGANIZATION_TERMS', 'orgterms')
      ->fields('orgterms', array('ORGANIZATION_TERM_ID'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_NAME', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'))
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->condition('orgterms.ORGANIZATION_ABBREVIATION', $school_abbreviation)
      ->orderBy('START_DATE', 'ASC', 'term')
      ->execute();
    while ($termsRow = $termsResult->fetch()) {
      $terms[] = array(
        'id' => $termsRow['ORGANIZATION_TERM_ID'],
        'name' => $termsRow['TERM_NAME'],
        'abbreviation' => $termsRow['TERM_ABBREVIATION'],
        'start_date' => $termsRow['START_DATE'],
        'end_date' => $termsRow['END_DATE']
      );
    }

    return $this->JSONResponse($terms);

  }

}