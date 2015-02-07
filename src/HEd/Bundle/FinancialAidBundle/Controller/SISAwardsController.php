<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISAwardsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student');
    
    $edit = $this->request->request->get('edit');
  
    if (count($edit) > 0) {
      $transaction = $this->db()->db_transaction();
      $this->processForm();
      if (isset($edit['HEd.FAID.Student.Award'])) {
      $awards_to_check = array();
        foreach($edit['HEd.FAID.Student.Award'] as $award_id => $award) {
          $awards_to_check[$award_id] = $award['HEd.FAID.Student.Award.NetAmount'];
        }
        $constituent_billing_service = $this->get('kula.HEd.billing.constituent')->adjustFinancialAidAward($awards_to_check);
      }
      $transaction->commit();
    }
    
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
    
    $fin_aid_year = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->condition('TERM_ID', $this->focus->getTermID())
      ->execute()->fetch();
    
    if ($this->record->getSelectedRecordID()) {

      $awards = $this->db()->db_select('FAID_STUDENT_AWARDS', 'faidstuawrds')
        ->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'DISBURSEMENT_DATE', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
        ->join('FAID_AWARD_CODE', 'awardcode', 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
        ->fields('awardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'))
        ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
        ->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'))
        ->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
        ->fields('faidstuawardyr', array('AWARD_YEAR'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID') 
        ->fields('org', array('ORGANIZATION_ABBREVIATION')) 
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
        ->condition('faidstuawardyr.AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdFinancialAidBundle:SISAwards:awards.html.twig', array('fin_aid_year' => $fin_aid_year['FINANCIAL_AID_YEAR'], 'awards' => $awards));
  }
  
  public function awards_historyAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student');
    
    $awards = array();
    
    if ($this->record->getSelectedRecordID()) {

      $awards = $this->db()->db_select('FAID_STUDENT_AWARDS', 'faidstuawrds')
        ->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'DISBURSEMENT_DATE', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
        ->join('FAID_AWARD_CODE', 'awardcode', 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
        ->fields('awardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'))
        ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
        ->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'))
        ->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
        ->fields('faidstuawardyr', array('AWARD_YEAR'))
        ->join('FAID_STUDENT_AWARD_YEAR_AWARDS', 'faidstuawrdyrawards', 'faidstuawrdyrawards.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID AND faidstuawrdyrawards.AWARD_CODE_ID = faidstuawrds.AWARD_CODE_ID')
        ->fields('faidstuawrdyrawards', array('GROSS_AMOUNT' => 'yr_GROSS_AMOUNT'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('faidstuawardyr.AWARD_YEAR', 'DESC')
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('awardcode.AWARD_CODE', 'ASC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdFinancialAidBundle:SISAwards:awards_history.html.twig', array('awards' => $awards));
  }
  
  public function billingAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student');
    
    $transactions = array();
    
    if ($this->record->getSelectedRecordID()) {

      $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE', 'CODE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'organization', 'organization.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('organization', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('transactions.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->condition('AWARD_ID', null, 'IS NOT NULL')
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('code.CODE', 'ASC')
        ->orderBy('transactions.TRANSACTION_DATE', 'DESC')
        ->orderBy('transactions.CONSTITUENT_TRANSACTION_ID', 'DESC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdFinancialAidBundle:SISAwards:billing.html.twig', array('transactions' => $transactions));
  }
  
  public function billing_historyAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student');
    
    $transactions = array();
    
    if ($this->record->getSelectedRecordID()) {

      $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE', 'CODE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'organization', 'organization.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('organization', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('AWARD_ID', null, 'IS NOT NULL')
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('code.CODE', 'ASC')
        ->orderBy('transactions.TRANSACTION_DATE', 'DESC')
        ->orderBy('transactions.CONSTITUENT_TRANSACTION_ID', 'DESC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdFinancialAidBundle:SISAwards:billing.html.twig', array('transactions' => $transactions));
  }
}