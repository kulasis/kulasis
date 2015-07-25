<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISHomeFinancialAidController extends Controller {
  
  public function pendingAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.FAID.AwardCode');
    
    if ($this->request->request->get('post')) {
      
      $transaction = $this->db()->db_transaction();
      
      $post = $this->request->request->get('post');
      
      foreach($post as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $this->get('kula.HEd.billing.constituent')->postFinancialAidAward($row_id);
        }
      }
      
      $transaction->commit();
    }
    
    $awards = array();
    $gross_total = 0;
    $net_total = 0;
    
    if ($this->record->getSelectedRecordID()) {
    
    $awards_result = $this->db()->db_select('FAID_STUDENT_AWARDS', 'faidstuawrds')
      ->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'DISBURSEMENT_DATE', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
      ->join('FAID_AWARD_CODE', 'awardcode', 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
      ->fields('awardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'))
      ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', 'awardterms.AWARD_YEAR_TERM_ID = faidstuawrds.AWARD_YEAR_TERM_ID')
      ->fields('awardterms', array('ORGANIZATION_TERM_ID'))
      ->join('FAID_STUDENT_AWARD_YEAR', 'stuawardyr', 'stuawardyr.AWARD_YEAR_ID = awardterms.AWARD_YEAR_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = stuawardyr.STUDENT_ID')
      ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
      ->leftJoin('BILL_CONSTITUENT_TRANSACTIONS', 'transactions', 'transactions.AWARD_ID = faidstuawrds.AWARD_ID')
      ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID'))
      ->condition('awardterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
      ->condition('transactions.CONSTITUENT_TRANSACTION_ID', null)
      ->condition('faidstuawrds.AWARD_STATUS', array('PEND', 'APPR'), 'IN')
      ->condition('faidstuawrds.SHOW_ON_STATEMENT', 1)
      ->condition('faidstuawrds.AWARD_CODE_ID', $this->record->getSelectedRecordID())
      ->orderBy('AWARD_STATUS', 'ASC', 'faidstuawrds')
      ->orderBy('LAST_NAME', 'ASC', 'constituent')
      ->orderBy('FIRST_NAME', 'ASC', 'constituent')
      ->orderBy('PERMANENT_NUMBER', 'ASC', 'constituent')
      ->execute();
      while ($awards_row = $awards_result->fetch()) {
        $awards[] = $awards_row;
        if ($awards_row['DISBURSEMENT_DATE'] != '') {
          $gross_total = bcadd($gross_total, $awards_row['GROSS_AMOUNT']);
          $net_total = bcadd($net_total, $awards_row['NET_AMOUNT']);
        }
      }
    }
    
    return $this->render('KulaHEdFinancialAidBundle:SISHomeFinancialAid:pending.html.twig', array('awards' => $awards, 'gross_total' => $gross_total, 'net_total' => $net_total));
  }
  
  public function postedAction() {
    $this->authorize();
    $this->setRecordType('SIS.FAID.AwardCode');
    
    $transactions = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE', 'CODE'))
        ->join('FAID_AWARD_CODE', 'faid_code', 'code.CODE_ID = faid_code.TRANSACTION_CODE_ID')
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'organization', 'organization.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('organization', array('ORGANIZATION_ABBREVIATION'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = transactions.CONSTITUENT_ID')
        ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
        ->condition('faid_code.AWARD_CODE_ID', $this->record->getSelectedRecordID())
        ->condition('transactions.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->condition('transactions.AWARD_ID', null, 'IS NOT NULL')
        ->orderBy('TRANSACTION_DATE', 'DESC', 'transactions')
        ->orderBy('LAST_NAME', 'ASC', 'constituent')
        ->orderBy('FIRST_NAME', 'ASC', 'constituent')
        ->orderBy('PERMANENT_NUMBER', 'ASC', 'constituent')
        ->execute()->fetchAll();

    }
    
    return $this->render('KulaHEdFinancialAidBundle:SISHomeFinancialAid:posted.html.twig', array('transactions' => $transactions));
  }
  
}