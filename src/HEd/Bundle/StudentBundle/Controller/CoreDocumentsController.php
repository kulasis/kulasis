<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreDocumentsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $documents = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $documents = $this->db()->db_select('STUD_STUDENT_DOCUMENTS', 'studocs')
        ->fields('studocs', array('STUDENT_DOCUMENT_ID', 'DOCUMENT_ID', 'DOCUMENT_DATE', 'DOCUMENT_STATUS', 'COMMENTS', 'COMPLETED_DATE'))
        ->join('STUD_DOCUMENT', 'doc', 'studocs.DOCUMENT_ID = doc.DOCUMENT_ID')
        ->fields('doc', array('DOCUMENT_NAME'))
        ->condition('studocs.STUDENT_ID', $this->record->getSelectedRecordID())
        ->condition('doc.INACTIVE', 0)
        ->orderBy('DOCUMENT_DATE', 'DESC', 'studocs')
        ->execute()->fetchAll();
        
    }
    
    return $this->render('KulaHEdStudentBundle:CoreDocuments:index.html.twig', array('documents' => $documents));
  }
}