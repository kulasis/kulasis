<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CorePaymentsController extends Controller {
  
  public function paymentsAction() {
    $this->authorize();

    if ($this->request->get('_route') == 'Core_Billing_ConstituentBilling_Payments') {
      $this->setRecordType('Core.Constituent');
    } else {
      $this->setRecordType('Core.HEd.Student');
    }
    
    if ($this->request->request->get('void')) {
      $payment_service = $this->get('kula.Core.billing.payment');
      
      $void = $this->request->request->get('void');
      $non = $this->request->request->get('non');
        
      if (isset($non['Core.Billing.Payment']['Core.Billing.Payment.PaymentDate']))
        $transaction_date = $non['Core.Billing.Payment']['Core.Billing.Payment.PaymentDate'];
      else 
        $transaction_date = null;
      
      if (isset($non['Core.Billing.Payment']['Core.Billing.Payment.VoidedReason']))
        $reason = $non['Core.Billing.Payment']['Core.Billing.Payment.VoidedReason'];
      else 
        $reason = null;
      
      foreach($void as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $payment_service->removeTransaction($row_id, $reason, $transaction_date);
        }
      }
    }
  
    $payments = array();
    
    if ($this->record->getSelectedRecordID()) {

      $payments = $this->db()->db_select('BILL_CONSTITUENT_PAYMENTS', 'payments')
        ->fields('payments', array('CONSTITUENT_PAYMENT_ID', 'PAYMENT_TYPE', 'PAYMENT_DATE', 'PAYMENT_METHOD', 'PAYMENT_NUMBER', 'AMOUNT', 'APPLIED_BALANCE', 'VOIDED'))
        ->leftJoin('BILL_CONSTITUENT_TRANSACTIONS', 'trans', "trans.PAYMENT_ID = payments.CONSTITUENT_PAYMENT_ID AND payments.PAYMENT_TYPE = 'D'")
        ->fields('trans', array('TRANSACTION_DESCRIPTION', 'STUDENT_CLASS_ID'))
        ->leftJoin('BILL_CODE', 'code', 'code.CODE_ID = trans.CODE_ID')
        ->fields('code', array('CODE'))
        ->leftJoin('STUD_STUDENT_CLASSES', 'stuclass', 'stuclass.STUDENT_CLASS_ID = trans.STUDENT_CLASS_ID')
        ->leftJoin('STUD_SECTION', 'sec', 'sec.SECTION_ID = stuclass.SECTION_ID')
        ->fields('sec', array('SECTION_NUMBER', 'SECTION_ID'))
        ->condition('payments.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('PAYMENT_DATE', 'DESC', 'payments')
        ->execute()->fetchAll();
        
    }
    
    return $this->render('KulaCoreBillingBundle:CorePayments:payments.html.twig', array('payments' => $payments));
  }

  public function payment_detailAction($payment_id) {
    $this->authorize();

    if ($this->request->get('_route') == 'Core_Billing_ConstituentBilling_PaymentDetail') {
      $this->setRecordType('Core.Constituent');
    } else {
      $this->setRecordType('Core.HEd.Student');
    }

    $this->processForm();

    $edit_post = $this->request->get('edit');
    $delete_post = $this->request->get('delete');
    
    if (isset($edit_post['Core.Billing.Payment'])) {
      // set balance amount
      foreach($edit_post['Core.Billing.Payment'] as $row_id => $row) {
        if (isset($row['Core.Billing.Payment.Amount'])) {
          $charge_detail_poster = $this->newPoster()->edit('Core.Billing.Payment', $row_id, array(
            'Core.Billing.Payment.AppliedBalance' => $row['Core.Billing.Payment.Amount'] * -1
          ))->process();
        }
      }
    }

    if (isset($edit_post['Core.Billing.Payment.Applied']) OR isset($delete_post['Core.Billing.Payment.Applied'])) {
      $payment_service = $this->get('kula.Core.billing.payment');
      $payment_service->calculateBalanceForPayment($payment_id);
    }
  
    $payment = array();
    $transactions = array();
    $applied_payments = array();
    $merchant_response = null;
    
    if ($this->record->getSelectedRecordID()) {
      $payment = $this->db()->db_select('BILL_CONSTITUENT_PAYMENTS', 'payments')
        ->fields('payments', array('CONSTITUENT_PAYMENT_ID', 'CONSTITUENT_ID', 'PAYEE_CONSTITUENT_ID', 'PAYMENT_TYPE', 'PAYMENT_DATE', 'PAYMENT_METHOD', 'PAYMENT_NUMBER', 'AMOUNT', 'APPLIED_BALANCE', 'VOIDED', 'MERCHANT_RESPONSE', 'NOTE'))
        ->condition('payments.CONSTITUENT_PAYMENT_ID', $payment_id)
        ->execute()->fetch();

      $merchant_response = print_r(unserialize($payment['MERCHANT_RESPONSE']), true);

      $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE', ))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE', 'CODE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->leftJoin('STUD_STUDENT_CLASSES', 'stuclass', 'stuclass.STUDENT_CLASS_ID = transactions.STUDENT_CLASS_ID')
        ->leftJoin('STUD_SECTION', 'sec', 'sec.SECTION_ID = stuclass.SECTION_ID')
        ->fields('sec', array('SECTION_NUMBER', 'SECTION_ID'))
        ->leftJoin('STUD_COURSE', 'crs', 'crs.COURSE_ID = sec.COURSE_ID')
        ->fields('crs', array('COURSE_TITLE'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('transactions.PAYMENT_ID', $payment_id)
        ->orderBy('TRANSACTION_DATE', 'DESC', 'transactions')
        ->execute()->fetchAll();

      $applied_payments = $this->db()->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
        ->fields('applied', array('CONSTITUENT_APPLIED_PAYMENT_ID', 'CONSTITUENT_PAYMENT_ID', 'CONSTITUENT_TRANSACTION_ID', 'AMOUNT', 'ORIGINAL_AMOUNT'))
        ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment_id)
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaCoreBillingBundle:CorePayments:payments_detail.html.twig', array('payment' => $payment, 'transactions' => $transactions, 'applied_payments' => $applied_payments, 'merchant_response' => $merchant_response));
  }

  public function addPaymentAction() {
    $this->authorize();

    if ($this->request->get('_route') == 'Core_Billing_ConstituentBilling_AddPayment') {
      $this->setRecordType('Core.Constituent');
    } else {
      $this->setRecordType('Core.HEd.Student');
    }
      
    if ($this->record->getSelectedRecordID()) {
      
      if ($this->request->request->get('add')) {
      
        $payment_service = $this->get('kula.Core.billing.payment');
        $add = $this->request->request->get('add');
        $payment_id = $payment_service->addPayment(
          $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.ConstituentID'], 
          $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.PayeeConstituentID'], 
          'P',
          $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.PaymentMethod'], 
          $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.PaymentDate'], 
          $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.PaymentNumber'], 
          $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.Amount'], 
          $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.Note']
        );

        // Transaction details set
        if (isset($add['Core.Billing.Transaction']['new_num']['Core.Billing.Transaction.CodeID']) AND 
          $add['Core.Billing.Transaction']['new_num']['Core.Billing.Transaction.CodeID'] != '' AND $payment_id != '') {

          $constituent_billing_service = $this->get('kula.Core.billing.transaction');
          $constituent_billing_service->addTransaction(
            $this->record->getSelectedRecordID(), 
            $add['Core.Billing.Transaction']['new_num']['Core.Billing.Transaction.OrganizationTermID']['value'], 
            $add['Core.Billing.Transaction']['new_num']['Core.Billing.Transaction.CodeID'], 
            $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.PaymentDate'], 
            $add['Core.Billing.Transaction']['new_num']['Core.Billing.Transaction.Description'], 
            $add['Core.Billing.Payment']['new_num']['Core.Billing.Payment.Amount'], 
            $payment_id
          );
        }
      
        if ($this->request->get('_route') == 'Core_Billing_ConstituentBilling_AddPayment') {
          return $this->forward('Core_Billing_ConstituentBilling_Payments', array('record_type' => 'Core.Constituent', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.Constituent', 'record_id' => $this->record->getSelectedRecordID()));
        } else {
          return $this->forward('Core_Billing_StudentBilling_Payments', array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()));
        }
      }
    
    }
    
    return $this->render('KulaCoreBillingBundle:CorePayments:payments_add.html.twig');
  }

  public function addAppliedPaymentAction($payment_id) {
    $this->authorize();
    
    if ($this->request->get('_route') == 'Core_Billing_ConstituentBilling_Payments_AddAppliedTransaction') {
      $this->setRecordType('Core.Constituent');
    } else {
      $this->setRecordType('Core.HEd.Student');
    }
      
    if ($this->record->getSelectedRecordID()) {
      
      if ($this->request->request->get('add')) {
      
        $payment_service = $this->get('kula.Core.billing.payment');
        $add = $this->request->request->get('add');
        $payment_service->addAppliedPayment(
          $payment_id,
          $add['Core.Billing.Payment.Applied']['new_num']['Core.Billing.Payment.Applied.TransactionID'],
          $add['Core.Billing.Payment.Applied']['new_num']['Core.Billing.Payment.Applied.Amount'],
          $add['Core.Billing.Payment.Applied']['new_num']['Core.Billing.Payment.Applied.Note']
        );
      
        if ($this->request->get('_route') == 'Core_Billing_ConstituentBilling_Payments_AddAppliedTransaction') {
          return $this->forward('Core_Billing_ConstituentBilling_PaymentDetail', array('record_type' => 'Core.Constituent', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.Constituent', 'record_id' => $this->record->getSelectedRecordID()), array('payment_id' => $payment_id));
        } else {
          return $this->forward('Core_Billing_StudentBilling_PaymentDetail', array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()), array('payment_id' => $payment_id));
        }
      }
    
    }
    
    return $this->render('KulaCoreBillingBundle:CorePayments:payments_add_applied.html.twig', array('payment_id' => $payment_id));
  }
  
}