<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISDocumentsSetupController extends Controller {
  
  public function document_codesAction() {
    $this->authorize();
    $this->processForm();
    
    $document_codes = $this->db()->db_select('STUD_DOCUMENT', 'docs')
      ->fields('docs')
      ->orderBy('DOCUMENT_CODE')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdStudentBundle:SISDocumentsSetup:document_codes.html.twig', array('document_codes' => $document_codes));
  }
  
}