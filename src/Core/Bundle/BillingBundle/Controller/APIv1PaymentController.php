<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class APIv1PaymentController extends APIController {

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

    // loop through pending charges
    $pending_charges = $pending_service->getPendingCharges();
    foreach($pending_charges as $charge) {
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
        throw new \Exception(print_r($merchant_service->getRawResult(), true));
      }

      // Only if amounts are the same
      if ($merchant_service->getResultAmount() == $pending_service->totalAmount()) {
        // lock all transactions
        $payment_service->lockAppliedPayments($payment_id);

        // calculate balances
        $payment_service->calculateBalance($payment_id);

        foreach($pending_charges as $charge) {
          // post pending charge
          $transaction_service->postTransaction($charge['CONSTITUENT_TRANSACTION_ID']);
          $transaction_service->calculateBalance($charge['CONSTITUENT_TRANSACTION_ID']);
        }
      }

      // return class list
      return $this->jsonResponse($merchant_service->getRawResult());
    } else {// end if on greater than zero total
      throw new \Exception('0.00 Amount');
    }
  }

}