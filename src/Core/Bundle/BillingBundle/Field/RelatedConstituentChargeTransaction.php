<?php

namespace Kula\Core\Bundle\BillingBundle\Field;

use Kula\Core\Component\Field\Field;

class RelatedConstituentChargeTransaction extends Field {
  
  public function select($schema, $param) {

    $menu = array();

    $result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'APPLIED_BALANCE'))
      ->join('BILL_CODE', 'codes', 'transactions.CODE_ID = codes.CODE_ID')
      ->fields('codes', array('CODE', 'CODE_TYPE'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = transactions.CONSTITUENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->leftJoin('CORE_TERM', 'term', 'orgterms.TERM_ID = term.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('transactions.CONSTITUENT_ID', $param['CONSTITUENT_ID'])
      ->condition('codes.CODE_TYPE', 'C')
      ->condition('transactions.APPLIED_BALANCE', '0', '!=')
      ->orderBy('LAST_NAME', 'ASC', 'cons')
      ->orderBy('FIRST_NAME', 'ASC', 'cons')
      ->orderBy('PERMANENT_NUMBER', 'ASC', 'cons')
      ->orderBy('START_DATE', 'DESC', 'term')
      ->orderBy('TRANSACTION_DATE', 'DESC')
      ->orderBy('CODE', 'ASC')
      ->execute();
    while ($row = $result->fetch()) {
      $menu[$row['CONSTITUENT_TRANSACTION_ID']] = $row['LAST_NAME'].', '.$row['FIRST_NAME'].' '.$row['PERMANENT_NUMBER'].' '.$row['ORGANIZATION_ABBREVIATION'].' '.$row['TERM_ABBREVIATION'].' '.date('m/d/Y', strtotime($row['TRANSACTION_DATE'])).' '.$row['CODE_TYPE'].' '.$row['CODE'].' '.$row['TRANSACTION_DESCRIPTION'].' '.$row['AMOUNT'].' '.$row['APPLIED_BALANCE'];
    }
    
    return $menu;
    
  }
  
}