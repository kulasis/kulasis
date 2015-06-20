<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreNonOrganizationController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    if ($this->session->get('portal') == 'sis') {
      $this->setRecordType('SIS.NonOrganization');
    } else {
      $this->setRecordType('Core.NonOrganization');
    }
    
    $nonorganization = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $nonorganization = $this->db()->db_select('CORE_NON_ORGANIZATION')
      ->fields('CORE_NON_ORGANIZATION')
      ->condition('NON_ORGANIZATION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();
    
    }
    
    return $this->render('KulaCoreSystemBundle:NonOrganization:index.html.twig', array('nonorganization' => $nonorganization));
  }
  
  public function addAction() {
    $this->authorize();
    $this->formAction('core_system_nonorganization_create');
    if ($this->session->get('portal') == 'sis') {
      $this->setRecordType('SIS.NonOrganization', 'Y');
    } else {
      $this->setRecordType('Core.NonOrganization', 'Y');
    }
    return $this->render('KulaCoreSystemBundle:NonOrganization:index.html.twig');
  }

  public function createAction() {
    $this->authorize();
    $this->processForm();
    $id = $this->poster->getPosterRecord('Core.NonOrganization', 0)->getID();
    
    if ($this->session->get('portal') == 'sis') {
      $redirect = array('record_type' => 'SIS.NonOrganization', 'record_id' => $id);
    } else {
      $redirect = array('record_type' => 'Core.NonOrganization', 'record_id' => $id);
    }
    
    return $this->forward('core_system_nonorganization', $redirect, $redirect);
  }
  
  public function deleteAction() {
    $this->authorize();
    if ($this->session->get('portal') == 'sis') {
      $this->setRecordType('SIS.NonOrganization');
    } else {
      $this->setRecordType('Core.NonOrganization');
    }
    
    $rows_affected = $this->db()->db_delete('CORE_NON_ORGANIZATION')->condition('NON_ORGANIZATION_ID', $this->record->getSelectedRecordID())->execute();
    
    if ($rows_affected == 1) {
      $this->flash->add('success', 'Deleted non organization.');
    }
    
    return $this->forward('core_system_nonorganization');
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.NonOrganization')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}