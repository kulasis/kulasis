<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kula\Core\Bundle\FrameworkBundle\Exception\DisplayException;

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
      ->condition('transactions.APPLIED_BALANCE', 0)
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->orderBy('TRANSACTION_DATE', 'DESC', 'transactions')
      ->execute();
    while ($transaction_row = $transactions_result->fetch()) {
      $data['transactions'][$i] = $transaction_row;
      $data['total'] += $transaction_row['AMOUNT'];
    $i++;
    }

    return $this->jsonResponse($data);
  }

  public function processPaymentAction() {

    // get logged in user
    $currentUser = $this->authorizeUser();

    // user info
    $user = $this->db()->db_select('CORE_USER', 'user')
      ->fields('user', array('USERNAME'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = user.USER_ID')
      ->fields('cons', array('FIRST_NAME', 'LAST_NAME', 'PERMANENT_NUMBER'))
      ->condition('user.USER_ID', $currentUser)
      ->execute()->fetch();

    $transaction_service = $this->get('kula.Core.billing.transaction');
    $transaction_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));

    // calculate pennding charges
    $pending_service = $this->get('kula.Core.billing.pending');
    $pending_service->calculatePendingCharges($currentUser);
    $pending_classes = $pending_service->getPendingClasses();
    
    if ($pending_service->totalAmount() > 0 AND $pending_service->totalAmount() <= 2000) {

      // Get payment type
      $payment_method = 
        isset($this->request->request->get('add')['Core.Billing.Payment'][0]['Core.Billing.Payment.PaymentMethod']) ? 
          $this->request->request->get('add')['Core.Billing.Payment'][0]['Core.Billing.Payment.PaymentMethod']
        :
          null;

      // create payment
      $payment_service = $this->get('kula.Core.billing.payment');
      $payment_service->setDBOptions(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
      $payment_id = $payment_service->addPayment(
        $currentUser, 
        $currentUser, 
        'P',
        $payment_method, 
        date('Y-m-d'), 
        null, 
        $pending_service->totalAmount()
      );
      $organization_term_id = (count($pending_service->getPendingClasses()) > 0) ? $pending_service->getPendingClasses()[0]['ORGANIZATION_TERM_ID'] : null;
      // loop through pending charges
      $pending_charges = $pending_service->getPendingCharges();
      foreach($pending_charges as $charge) {
        if ($charge['CODE_TYPE'] == 'C') {
          // apply charge to payment
          $payment_service->addAppliedPayment(
            $payment_id, 
            $charge['CONSTITUENT_TRANSACTION_ID'], 
            $charge['APPLIED_BALANCE'],
            null,
            1
          );
        } // if on code type of charge 
      } // end loop on pending charges


      if (($payment_method == 'CREDIT' OR $payment_method == 'DEBIT')) {
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

        // Only if amounts are the same
        if ($merchant_service->getResultAmount() == $pending_service->totalAmount() AND !$merchant_service->getError()) {
          // Add payment transaction 
          $transaction_payment_id = $transaction_service->addTransaction(
            $currentUser, 
            $organization_term_id, 
            122, 
            date('Y-m-d'), 
            null, 
            $merchant_service->getResultAmount(), 
            $payment_id
          );

          // lock all transactions
          $payment_service->lockAppliedPayments($payment_id);

          // calculate balances
          $payment_service->calculateBalanceForPayment($payment_id);

          foreach($pending_charges as $charge) {
            // post pending charges
            $transaction_service->postTransaction($charge['CONSTITUENT_TRANSACTION_ID']);
            $payment_service->calculateBalanceForCharge($charge['CONSTITUENT_TRANSACTION_ID']);
          }

          // post payment
          $transaction_service->postTransaction($transaction_payment_id);

          // send email
          $message = \Swift_Message::newInstance()
            ->setSubject('OCAC Web Order Number '.$payment_id)
            ->setFrom(['kulasis@ocac.edu' => 'Oregon College of Art and Craft'])
            ->setReplyTo('cmalone@ocac.edu')
            ->setTo($user['USERNAME'])
            ->setBcc(array('cmalone@ocac.edu', 'mjacobsen@ocac.edu'))
            ->setBody(
                $this->renderView(
                    'KulaCoreBillingBundle:CoreEmail:purchase.text.twig',
                    array('merchant' => $merchant_service->getRawResult(), 'payment_id' => $payment_id, 'pending' => $pending_classes, 'user' => $user)
                ),
                'text/plain');
          $this->get('mailer')->send($message);

        } else {
          // void all payment items
          $payment_service->voidPayment($payment_id);

          $exception = new DisplayException('Processing Payment Error');
          $exception->setData($merchant_service->getRawResult());
          throw $exception;
        }

        // return class list
        return $this->jsonResponse($merchant_service->getRawResult());
      } elseif ($payment_method == 'CHK' AND $pending_service->totalAmount() > 0) {

        // Add payment transaction 
        $transaction_service->addTransaction(
          $currentUser, 
          $organization_term_id, 
          122, 
          date('Y-m-d'), 
          null, 
          $pending_service->totalAmount(), 
          $payment_id
        );

        return $this->jsonResponse($payment_id);

      } else {
        throw new DisplayException('Invalid payment method.  Payment method is '. $payment_method);
      }
    } else { // end if on greater than zero total
        throw new DisplayException('0.00 or greater than 2000.00 amount.  Amount is '.$pending_service->totalAmount().'.');
    }
  }

}