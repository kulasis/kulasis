<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1LookupController extends APIController {

  public function getLookupTableAction($lookup_table) {
    $this->authorize();

    // check if lookup table exists
    $table = $this->db()->db_select('CORE_LOOKUP_TABLES', 'tables')
      ->fields('tables', array('LOOKUP_TABLE_ID'))
      ->condition('tables.LOOKUP_TABLE_NAME', $lookup_table)
      ->execute()->fetch();

    if ($table['LOOKUP_TABLE_ID']) { 
      // get as of date
      $as_of_date = $this->request->query->get('as_of_date');

      $db_or = $this->db()->db_or()
        ->isNull('values.INACTIVE_AFTER')
        ->condition('values.INACTIVE_AFTER', $as_of_date, '>=');

      $values = array();
      $valuesResult = $this->db()->db_select('CORE_LOOKUP_VALUES', 'values')
        ->fields('values', array('CODE', 'DESCRIPTION'))
        ->join('CORE_LOOKUP_TABLES', 'tables', 'tables.LOOKUP_TABLE_ID = values.LOOKUP_TABLE_ID')
        ->condition('tables.LOOKUP_TABLE_NAME', $lookup_table)
        ->condition($db_or)
        ->orderBy('SORT', 'ASC', 'values')
        ->orderBy('CODE', 'ASC', 'values')
        ->execute();
      while ($valuesRow = $valuesResult->fetch()) {
        $values[$valuesRow['CODE']] = $valuesRow['DESCRIPTION'];
      }

      return $this->JSONResponse($values);
  
    } else {
      throw $this->createNotFoundException('The lookup table does not exist');
    } // end check if lookup table exists
  }

}