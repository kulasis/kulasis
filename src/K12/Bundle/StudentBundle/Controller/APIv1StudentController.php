<?php

namespace Kula\K12\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1StudentController extends APIController {

  public function relatedChildrenAction() {

    $currentUser = $this->authorizeUser();

    $data = array();

    $data = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $currentUser)
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = rel.CONSTITUENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->execute()->fetchAll();

    return $this->JSONResponse($data);
  }

  public function createChildAction() {
    $this->authorize();

    $transaction = $this->db()->db_transaction('create_user');

    // create constituent
    $constituent_service = $this->get('Kula.Core.Constituent');
    $constituent_data = $this->form('add', 'Core.Constituent', 0);
    $constituent_id = $constituent_service->createConstituent($constituent_data);

    // create user
    $user_service = $this->get('Kula.Core.User');
    $user_data = $this->form('add', 'Core.User', 0);
    $user_data['Core.User.ID'] = $constituent_id;
    $user_id = $user_service->createUser($user_data);

    // create constituent relationship
    $this->newPoster()->add('Core.Constituent.Relationship', 0, array(
      
    ))->process();
    
    $contactInfo_service = $this->get('Kula.Core.ContactInfo');
    // add address
    $address_data = $this->form('add', 'Core.Constituent.Address');
    if (count($address_data) > 0) {
      foreach($address_data as $id => $fields) {
        if ($fields['Core.Constituent.Address.Thoroughfare'] != '') {
          $fields['Core.Constituent.Address.ConstituentID'] = $constituent_id;
          $fields['Core.Constituent.Address.EffectiveDate'] = date('m/d/Y');
          $addressID = $contactInfo_service->addAddress($id, $fields);
        }
      }
    }

    // add phone
    $phone_data = $this->form('add', 'Core.Constituent.Phone');
    if (count($phone_data) > 0) {
      foreach($phone_data as $id => $fields) {
        if ($fields['Core.Constituent.Phone.Number'] != '') {
          $fields['Core.Constituent.Phone.ConstituentID'] = $constituent_id;
          $fields['Core.Constituent.Phone.EffectiveDate'] = date('m/d/Y');
          $phoneID = $contactInfo_service->addPhone($id, $fields);
        }
      }
    }

    // add email
    $email_data = $this->form('add', 'Core.Constituent.EmailAddress');
    if (count($email_data) > 0) {
      foreach($email_data as $id => $fields) {
        if ($fields['Core.Constituent.EmailAddress.EmailAddress']) {
          $fields['Core.Constituent.EmailAddress.ConstituentID'] = $constituent_id;
          $fields['Core.Constituent.EmailAddress.EffectiveDate'] = date('m/d/Y');
          $emailID = $contactInfo_service->addEmail($id, $fields);
        }
      }
    }

    if ($user_id) {
      $transaction->commit();
      return $this->JSONResponse($constituent_id);
    } else {
      $transaction->rollback();
    }

  }

}