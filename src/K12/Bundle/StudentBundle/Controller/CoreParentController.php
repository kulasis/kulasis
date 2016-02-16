<?php

namespace Kula\K12\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreParentController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Parent');
    
    $addresses = array();
    $phones = array();
    $emails = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $selected_record = $this->record->getSelectedRecord();
      
      $address_result = $this->db()->db_select('CONS_ADDRESS', null)
        ->fields(null, array('ADDRESS_ID', 'ADDRESS_TYPE', 'EFFECTIVE_DATE', 'THOROUGHFARE', 'LOCALITY', 'ADMINISTRATIVE_AREA', 'POSTAL_CODE', 'COUNTRY'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('ADDRESS_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute();
      $address_type = '';
      while ($address_row = $address_result->fetch()) {
        if ($address_type != $address_row['ADDRESS_TYPE'])
          $addresses[$address_row['ADDRESS_TYPE']] = $address_row;
        $address_type = $address_row['ADDRESS_TYPE'];
      }
      
      $phone_result = $this->db()->db_select('CONS_PHONE', null)
        ->fields(null, array('PHONE_NUMBER_ID', 'EFFECTIVE_DATE', 'PHONE_TYPE', 'PHONE_NUMBER', 'PHONE_EXTENSION', 'PHONE_COUNTRY'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('PHONE_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute();
      $phone_type = '';
      while ($phone_row = $phone_result->fetch()) {
        if ($phone_type != $phone_row['PHONE_TYPE'])
          $phones[$phone_row['PHONE_TYPE']] = $phone_row;
        $phone_type = $phone_row['PHONE_TYPE'];
      }
      
      $email_result = $this->db()->db_select('CONS_EMAIL_ADDRESS', null)
        ->fields(null, array('EMAIL_ADDRESS_ID', 'EMAIL_ADDRESS_TYPE', 'EFFECTIVE_DATE', 'EMAIL_ADDRESS'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('EMAIL_ADDRESS_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute();
      $email_type = '';
      $i = 0;
      while ($email_row = $email_result->fetch()) {
        if ($email_row['EMAIL_ADDRESS_TYPE'] == null)
          $email_row['EMAIL_ADDRESS_TYPE'] = 'OT' . $i;
        if ($email_type != $email_row['EMAIL_ADDRESS_TYPE'])
          $emails[$email_row['EMAIL_ADDRESS_TYPE']] = $email_row;
        $email_type = $email_row['EMAIL_ADDRESS_TYPE'];
        $i++;
      }
      
      
    } // end if selected record
    
    return $this->render('KulaK12StudentBundle:CoreParent:index.html.twig', array('addresses' => $addresses, 'phones' => $phones, 'emails' => $emails));
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Parent');
    
    if ($this->newPoster()->delete('K12.Parent', $this->record->getSelectedRecordID())->process()->getResult()) {
      $this->addFlash('success', 'Deleted parent.');
    } else {
      $this->addFlash('error', 'Unable to delete parent.');
    }
    
    return $this->forward('Core_K12_Student_Parent_Basic');
    
  }
  
  public function childrenAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Parent');
    
    if ($this->form('delete', 'K12.Student.Parent')) {
      $relationshipID = key($this->form('delete', 'K12.Student.Parent'));
      $this->newPoster()->delete('Core.Constituent.Relationship', $relationshipID)->process()->getResult();
    }
    
    $children = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $children = $this->db()->db_select('STUD_STUDENT_PARENTS', 'stupar')
      ->fields('stupar')
      ->join('CONS_RELATIONSHIP', 'conrel', 'conrel.RELATIONSHIP_ID = stupar.STUDENT_PARENT_ID')
      ->fields('conrel', array('RELATIONSHIP'))
      ->join('CONS_CONSTITUENT', 'constu', 'constu.CONSTITUENT_ID = conrel.CONSTITUENT_ID')
      ->fields('constu', array('LAST_NAME', 'FIRST_NAME', 'CONSTITUENT_ID' => 'STUDENT_ID'))
      ->condition('conrel.RELATED_CONSTITUENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    }
  
    return $this->render('KulaK12StudentBundle:CoreParent:children.html.twig', array('children' => $children));
    
  }
  
}