<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\DisplayException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class APIv1PaymentController extends APIController {

  public function getStatementAction($org) {

    // get logged in user
    $currentUser = $this->authorizeUser();

    $data = array();

    $data['total'] = 0;
    $i = 0;
    $transactions_result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
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
      ->leftJoin('BILL_CONSTITUENT_PAYMENTS', 'payments', 'payments.CONSTITUENT_PAYMENT_ID = transactions.PAYMENT_ID')
      ->fields('payments', array('PAYMENT_TYPE', 'PAYMENT_DATE', 'PAYMENT_NUMBER'))
      ->condition('transactions.CONSTITUENT_ID', $currentUser)
      ->condition('transactions.POSTED', 1)
      ->condition('transactions.SHOW_ON_STATEMENT', 1)
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->orderBy('TRANSACTION_DATE', 'DESC', 'transactions')
      ->execute();
    while ($transaction_row = $transactions_result->fetch()) {
      $data['transactions'][$i] = $transaction_row;
      $data['total'] += $transaction_row['AMOUNT'];
    $i++;
    }

    return $this->jsonResponse($transactions);
  }

  public function processPaymentAction() {

    // get logged in user
    $currentUser = $this->authorizeUser();

    // user info
    $user = $this->db()->db_select('CORE_USER', 'user')
      ->fields('user', array('USERNAME'))
      ->condition('user.USER_ID', $currentUser)
      ->execute()->fetch();

    $transaction_service = $this->get('kula.Core.billing.transaction');
    $transaction_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

    // calculate pennding charges
    $pending_service = $this->get('kula.Core.billing.pending');
    $pending_service->calculatePendingCharges($currentUser);

    // create payment
    $payment_service = $this->get('kula.Core.billing.payment');
    $payment_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
    $payment_id = $payment_service->addPayment(
      $currentUser, 
      $currentUser, 
      'CREDIT', 
      date('Y-m-d'), 
      null, 
      $pending_service->totalAmount()
    );
    $organization_term_id = null;
    // loop through pending charges
    $pending_charges = $pending_service->getPendingCharges();
    foreach($pending_charges as $charge) {
      $organization_term_id = $charge['ORGANIZATION_TERM_ID'];
      // apply charge to payment
      $payment_service->addAppliedPayment(
        $payment_id, 
        $charge['CONSTITUENT_TRANSACTION_ID'], 
        $charge['AMOUNT'],
        null,
        1
      );

    } // end loop on pending charges

    if ($pending_service->totalAmount() > 0) {
      // Send payment to processor
      $merchant_service = $this->get('kula.Core.billing.payment.merchant.VirtualMerchant');

      $result = $merchant_service->process(
        $pending_service->totalAmount(), 
        $this->request->request->get('cc_first_name'), 
        $this->request->request->get('cc_last_name'), 
        $user['USERNAME'], 
        $this->request->request->get('cc_number'), 
        $this->request->request->get('cc_exp_date'), 
        $this->request->request->get('cc_cvv'),
        $this->request->request->get('cc_address'), 
        $this->request->request->get('cc_city'), 
        $this->request->request->get('cc_state'), 
        $this->request->request->get('cc_zip_code'),
        $payment_id
      );

      // Post result from processor
      $apply_result = $payment_service->applyMerchantResponse(
        $payment_id, 
        $merchant_service->getTransactionID(), 
        $merchant_service->getResultAmount(), 
        date('Y-m-d H:i:s'), 
        serialize($merchant_service->getRawResult())
      );

      if ($merchant_service->getError()) {
        throw new DisplayException(print_r($merchant_service->getRawResult(), true));
      }

      // Add payment transaction 
      $transaction_service->addTransaction(
        $currentUser, 
        $organization_term_id, 
        122, 
        date('Y-m-d'), 
        null, 
        $merchant_service->getResultAmount(), 
        $payment_id
      );

      // Only if amounts are the same
      if ($merchant_service->getResultAmount() == $pending_service->totalAmount()) {
        // lock all transactions
        $payment_service->lockAppliedPayments($payment_id);

        // calculate balances
        $payment_service->calculateBalanceForPayment($payment_id);

        foreach($pending_charges as $charge) {
          // post pending charge
          $transaction_service->postTransaction($charge['CONSTITUENT_TRANSACTION_ID']);
          $payment_service->calculateBalanceForCharge($charge['CONSTITUENT_TRANSACTION_ID']);
        }
      }

      // return class list
      return $this->jsonResponse($merchant_service->getRawResult());
    } else {// end if on greater than zero total
      throw new DisplayException('0.00 Amount');
    }
  }

}