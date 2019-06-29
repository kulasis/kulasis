<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreCodesController extends Controller {
  
  public function codesAction() {
    $this->authorize();
    $this->setRecordType('Core.Billing.BillingCode');
    $this->processForm();
    
    $code = array();
    if ($this->record->getSelectedRecordID()) {

	    $code = $this->db()->db_select('BILL_CODE')
	      ->fields('BILL_CODE')
	      ->condition('CODE_ID', $this->record->getSelectedRecordID())
	      ->execute()->fetch();
 
    }
    
    return $this->render('KulaCoreBillingBundle:CoreCodes:codes.html.twig', array('code' => $code));
  }

  public function addAction() {
    $this->authorize();
    $this->setRecordType('Core.Billing.BillingCode', 'Y');
    $this->formAction('Core_Billing_BillingSetup_Codes_Create');
    return $this->render('KulaCoreBillingBundle:CoreCodes:add.html.twig');
  }
  
  public function createAction() {
    $this->authorize();
    $this->processForm();
    $id = $this->poster()->getResult();
    
    if ($id) {
      $this->addFlash('success', 'Created section.');
      return $this->forward('Core_Billing_BillingSetup_Codes', array('record_type' => 'Core.Billing.BillingCode', 'record_id' => $id), array('record_type' => 'Core.Billing.BillingCode', 'record_id' => $id));
    } else {
    	$this->formAction('Core_Billing_BillingSetup_Codes_Create');
    	return $this->render('KulaCoreBillingBundle:CoreCodes:add.html.twig');
    }

  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.Billing.BillingCode');
    
    $rows_affected = $this->db()->db_delete('BILL_CODE')
        ->condition('CODE_ID', $this->record->getSelectedRecordID())->execute();
    
    if ($rows_affected == 1) {
      $this->addFlash('success', 'Deleted billing code.');
    }
    
    return $this->forward('Core_Billing_BillingSetup_Codes');
  }
  
}