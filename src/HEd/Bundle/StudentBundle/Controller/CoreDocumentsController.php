<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class CoreDocumentsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    
    if ($delete = $this->form('delete', 'HEd.Student.Document')) {
      foreach($delete as $id => $record) {
        // Check if id
        $doc_id = $this->db()->db_select('STUD_STUDENT_DOCUMENTS', 'studocs')
          ->fields('studocs', array('ATTACHED_DOC_ID'))
          ->condition('studocs.STUDENT_DOCUMENT_ID', $id)
          ->execute()->fetch()['ATTACHED_DOC_ID'];
        $this->get('kula.Core.Constituent.File')->removeDocument($doc_id);
      }
    }
    
    $this->processForm();
    /*
    if ($edit = $this->form('edit', 'HEd.Student.Document')) {
      foreach($edit as $id => $table_record) {
        foreach($table_record as $table => $record) {
          // Check if id
          $doc_id = $this->db()->db_select('STUD_STUDENT_DOCUMENTS', 'studocs')
            ->fields('studocs', array('ATTACHED_DOC_ID'))
            ->condition('studocs.STUDENT_DOCUMENT_ID', $id)
            ->execute()->fetch()['ATTACHED_DOC_ID'];

          $this->get('kula.Core.Constituent.File')->removeDocument($doc_id);
        }
      }
    }
*/
    if ($this->request->files) {
      foreach($this->request->files as $table) {
        foreach($table as $table_name => $id) {
          foreach($id as $record_id => $field) {
            foreach($field as $field_name => $file) {
              if ($file instanceof UploadedFile) {
                if ($file->isValid()) {

                  $filename = uniqid().".".$file->getClientOriginalExtension();
                  $path = "/tmp";
                  $mime = $file->getMimeType();
                  $original_name = $file->getClientOriginalName();
                  $file->move($path,$filename); // move the file to a path

                  $id = $this->get('kula.Core.Constituent.File')->addFile(
                    $mime,
                    $original_name,
                    file_get_contents($path.'/'.$filename)
                  );

                  if ($id) {
                    unlink($path.'/'.$filename);
                    // Link to 
                    $this->newPoster()->edit($table_name, $record_id, array($field_name => $id))->process();
                  }
                }
              }
            }
          }
        }
      }
    }

    $documents = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $documents = $this->db()->db_select('STUD_STUDENT_DOCUMENTS', 'studocs')
        ->fields('studocs', array('STUDENT_DOCUMENT_ID', 'DOCUMENT_ID', 'DOCUMENT_DATE', 'DOCUMENT_STATUS', 'COMMENTS', 'COMPLETED_DATE', 'ATTACHED_DOC_ID'))
        ->join('STUD_DOCUMENT', 'doc', 'studocs.DOCUMENT_ID = doc.DOCUMENT_ID')
        ->fields('doc', array('DOCUMENT_NAME'))
        ->leftJoin('CONS_DOCUMENTS', 'attach_docs', 'attach_docs.CONSTITUENT_DOCUMENT_ID = studocs.ATTACHED_DOC_ID', null, array('target' => 'additional'))
        ->fields('attach_docs', array('CONTENT_TYPE', 'FILE_NAME'))
        ->condition('studocs.STUDENT_ID', $this->record->getSelectedRecordID())
        ->condition('doc.INACTIVE', 0)
        ->orderBy('DOCUMENT_DATE', 'DESC', 'studocs')
        ->execute()->fetchAll();
        
    }
    
    return $this->render('KulaHEdStudentBundle:CoreDocuments:index.html.twig', array('documents' => $documents));
  }

  public function getDocumentAction($document_id) {
    $this->authorize();

    // Get document
    $file = $this->get('kula.Core.Constituent.File')->getFile($document_id);

    if ($file) {
      // Generate response
      $response = new Response();

      // Set headers
      $response->headers->set('Cache-Control', 'private');
      $response->headers->set('Pragma', 'private');
      $response->headers->set('Content-Type', $file['CONTENT_TYPE']);
      //$response->headers->set('Content-Type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', 'inline; filename='.$file['FILE_NAME']);
      $response->headers->set('Expires', '0');

      // Send headers before outputting anything
      $response->sendHeaders();
    
      $response->setContent($file['CONTENTS']);
      return $response;
      
    } else {
      $response = new Response();
      $response->setContent('No document file.');
      return $response;
    }

  }

  public function deleteDocumentAction($document_id) {
    $this->authorize();

    // Get document
    $file = $this->get('kula.Core.Constituent.File')->getFile($document_id);

    if ($file AND $this->get('kula.Core.Constituent.File')->removeDocument($document_id)) {
      $response = new Response('File deleted.');
    } else {
      $response = new Response('File does not exist.');
    }

  }
}