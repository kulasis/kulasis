<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1UserController extends APIController {

  public function currentUserAction() {

    $currentUser = $this->authorizeUser();

    return $this->getUserViaAPI($currentUser);
  }

  public function userAction($user_id) {
    $this->authorize();

    return $this->getUserViaAPI($user_id);
  }

  private function getUserViaAPI($user_id) {
    $currentUser = $this->authorizeUser();
    $related_constituents = array();
    $user = array();

    // Get related constituents
    $related_constituents_result = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $currentUser)
      ->execute();
    while ($related_constituent = $related_constituents_result->fetch()) {
      $related_constituents[] = $related_constituent['CONSTITUENT_ID'];
    }
    $related_constituents[] = $currentUser;

    if (in_array($user_id, $related_constituents)) {

    $userResult = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
      ->fields('cons', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'PERMANENT_NUMBER'))
      ->leftJoin('CONS_ADDRESS', 'addr_res', 'addr_res.ADDRESS_ID = cons.RESIDENCE_ADDRESS_ID AND addr_res.ACTIVE = 1')
      ->fields('addr_res', array('ADDRESS_ID' => 'addr_res_ID', 'THOROUGHFARE' => 'addr_res_address', 'LOCALITY' => 'addr_res_city', 'ADMINISTRATIVE_AREA' => 'addr_res_state', 'POSTAL_CODE' => 'addr_res_zipcode'))
      ->leftJoin('CONS_ADDRESS', 'addr_mail', 'addr_mail.ADDRESS_ID = cons.MAILING_ADDRESS_ID AND addr_mail.ACTIVE = 1')
      ->fields('addr_mail', array('ADDRESS_ID' => 'addr_mail_ID', 'THOROUGHFARE' => 'addr_mail_address', 'LOCALITY' => 'addr_mail_city', 'ADMINISTRATIVE_AREA' => 'addr_mail_state', 'POSTAL_CODE' => 'addr_mail_zipcode'))
      ->leftJoin('CONS_PHONE', 'phone_primary', 'phone_primary.PHONE_NUMBER_ID = cons.PRIMARY_PHONE_ID AND phone_primary.ACTIVE = 1')
      ->fields('phone_primary', array('PHONE_NUMBER_ID', 'PHONE_TYPE', 'PHONE_NUMBER', 'PHONE_EXTENSION'))
      ->leftJoin('CONS_EMAIL_ADDRESS', 'email_primary', 'email_primary.EMAIL_ADDRESS_ID = cons.PRIMARY_EMAIL_ID AND email_primary.ACTIVE = 1')
      ->fields('email_primary', array('EMAIL_ADDRESS_ID', 'EMAIL_ADDRESS_TYPE', 'EMAIL_ADDRESS'))
      ->join('CORE_USER', 'user', 'user.USER_ID = cons.CONSTITUENT_ID')
      ->fields('user', array('USERNAME'))
      ->condition('cons.CONSTITUENT_ID', $user_id)
      ->execute();
    while ($userRow = $userResult->fetch()) {

      $user = array(
        'Core.Constituent.ID' => $userRow['CONSTITUENT_ID'],
        'Core.Constituent.LastName' => $userRow['LAST_NAME'],
        'Core.Constituent.FirstName' => $userRow['FIRST_NAME'],
        'Core.Constituent.PermanentNumber' => $userRow['PERMANENT_NUMBER'],
        'Core.Constituent.MiddleName' => $userRow['MIDDLE_NAME'],
        'Core.User.Username' => $userRow['USERNAME']
      );

      // Addresses
      $user['residence_address'] = array(
        'Core.Constituent.Address.ID' => $userRow['addr_res_ID'],
        'Core.Constituent.Address.Thoroughfare' => $userRow['addr_res_address'],
        'Core.Constituent.Address.Locality' => $userRow['addr_res_city'],
        'Core.Constituent.Address.AdministrativeArea' => $userRow['addr_res_state'],
        'Core.Constituent.Address.PostalCode' => $userRow['addr_res_zipcode']
      );

      $user['mailing_address'] = array(
        'Core.Constituent.Address.ID' => $userRow['addr_mail_ID'],
        'Core.Constituent.Address.Thoroughfare' => $userRow['addr_mail_address'],
        'Core.Constituent.Address.Locality' => $userRow['addr_mail_city'],
        'Core.Constituent.Address.AdministrativeArea' => $userRow['addr_mail_state'],
        'Core.Constituent.Address.PostalCode' => $userRow['addr_mail_zipcode']
      );

      // Phones
      $user['phone'] = array(
        'Core.Constituent.Phone.ID' => $userRow['PHONE_NUMBER_ID'],
        'Core.Constituent.Phone.Type' => $userRow['PHONE_TYPE'],
        'Core.Constituent.Phone.Number' => $userRow['PHONE_NUMBER'],
        'Core.Constituent.Phone.Extension' => $userRow['PHONE_EXTENSION']
      );

      // Emails
      $user['email'] = array(
        'Core.Constituent.EmailAddress.ID' => $userRow['EMAIL_ADDRESS_ID'],
        'Core.Constituent.EmailAddress.Type' => $userRow['EMAIL_ADDRESS_TYPE'],
        'Core.Constituent.EmailAddress.EmailAddress' => $userRow['EMAIL_ADDRESS']
      );
      
    }
    } // end if on related constituents
    return $this->JSONResponse($user);
  }

  public function createUser() {


  }

}