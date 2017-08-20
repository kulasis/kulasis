<?php

namespace Kula\Core\Bundle\ConstituentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class ConstituentDocumentController extends Controller {
  
  public function getDocumentAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Constituent');

    $constituent = array();
    
    $constituent = $this->db()->db_select('CONS_CONSTITUENT', 'constituent')
      ->fields('constituent')
      ->condition('CONSTITUENT_ID', $this->record->getSelectedRecord()['CONSTITUENT_ID'])
      ->execute()->fetch();
    
    return $this->render('KulaCoreConstituentBundle:Constituent:index.html.twig', array('constituent' => $constituent));
  }
  
}
