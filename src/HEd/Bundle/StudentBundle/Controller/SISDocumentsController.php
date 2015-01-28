<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISDocumentsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('HEd.Student');
    
    $documents = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $documents = $this->db()->db_select('STUD_STUDENT_DOCUMENTS', 'studocs')
        ->fields('studocs', array('STUDENT_DOCUMENT_ID', 'DOCUMENT_ID', 'DOCUMENT_DATE', 'DOCUMENT_STATUS', 'COMMENTS', 'COMPLETED_DATE'))
        ->join('STUD_DOCUMENT', 'doc', array('DOCUMENT_NAME'), 'studocs.DOCUMENT_ID = doc.DOCUMENT_ID')
        ->condition('studocs.STUDENT_ID', $this->record->getSelectedRecordID())
        ->condition('doc.INACTIVE', 0)
        ->orderBy('DOCUMENT_DATE', 'DESC', 'studocs')
        ->execute()->fetchAll();
        
    }
    
    return $this->render('KulaHEdStudentBundle:SISDocuments:index.html.twig', array('documents' => $documents));
  }
}