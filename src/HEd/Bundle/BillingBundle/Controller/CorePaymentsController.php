<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CorePaymentsController extends Controller {
  
  public function paymentsAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    
    /*
    if ($this->request->request->get('void')) {
      $constituent_billing_service = $this->get('kula.HEd.billing.constituent');
      
      $void = $this->request->request->get('void');
      $non = $this->request->request->get('non');
        
      if (isset($non['HEd.Billing.Transaction']['HEd.Billing.Transaction.TransactionDate']))
        $transaction_date = $non['HEd.Billing.Transaction']['HEd.Billing.Transaction.TransactionDate'];
      else 
        $transaction_date = null;
      
      if (isset($non['HEd.Billing.Transaction']['HEd.Billing.Transaction.VoidedReason']))
        $reason = $non['HEd.Billing.Transaction']['HEd.Billing.Transaction.VoidedReason'];
      else 
        $reason = null;
      
      foreach($void as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $constituent_billing_service->removeTransaction($row_id, $reason, $transaction_date);
        }
      }
    }
    */
    $payments = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $payments = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->join('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'payments_applied', 'payments_applied.CONSTITUENT_TRANSACTION_ID = transactions.CONSTITUENT_TRANSACTION_ID')
        ->join('BILL_CONSTITUENT_PAYMENTS', 'payments', 'payments.CONSTITUENT_PAYMENT_ID = payments_applied.CONSTITUENT_PAYMENT_ID')
        ->fields('payments', array('CONSTITUENT_PAYMENT_ID', 'PAYMENT_TYPE', 'PAYMENT_DATE', 'PAYMENT_METHOD', 'PAYMENT_NUMBER', 'AMOUNT', 'APPLIED_BALANCE', 'VOIDED'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('transactions.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->orderBy('PAYMENT_DATE', 'DESC', 'payments')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdBillingBundle:CorePayments:payments.html.twig', array('payments' => $payments));
  }

  public function payment_detailAction($payment_id) {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    $this->processForm();
/*
    $edit_post = $this->request->get('edit');
    
    if (isset($edit_post['HEd.Billing.Transaction'])) {
      // set balance amount
      foreach($edit_post['HEd.Billing.Transaction'] as $row_id => $row) {
        if (isset($row['HEd.Billing.Transaction.Amount'])) {
          $charge_detail_poster = $this->newPoster()->edit('HEd.Billing.Transaction', $row_id, array(
            'HEd.Billing.Transaction.AppliedBalance' => $row['HEd.Billing.Transaction.Amount']
          ))->process();
        }
      }
    }
  */  
    $payment = array();
    
    if ($this->record->getSelectedRecordID()) {
      $payment = $this->db()->db_select('BILL_CONSTITUENT_PAYMENTS', 'payments')
        ->fields('payments', array('CONSTITUENT_PAYMENT_ID', 'PAYMENT_TYPE', 'PAYMENT_DATE', 'PAYMENT_METHOD', 'PAYMENT_NUMBER', 'AMOUNT', 'APPLIED_BALANCE', 'VOIDED'))
        ->condition('payments.CONSTITUENT_PAYMENT_ID', $payment_id)
        ->execute()->fetch();
      
    }
    
    return $this->render('KulaHEdBillingBundle:CoreTransactions:transactions_detail.html.twig', array('payment' => $payment));
  }
  
}