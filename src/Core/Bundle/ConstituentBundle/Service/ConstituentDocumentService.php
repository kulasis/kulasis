<?php

namespace Kula\Core\Bundle\ConstituentBundle\Service;

class ConstituentDocumentService {
  
  public function __construct($db, $session, $poster) {
    $this->db = $db;
    $this->session = $session;
    $this->poster = $poster;
  }
  
  public function addFile($constituent_id, $content_type, $file_name, $contents) {

    $poster = $this->poster->newPoster();

    $data = array(
      'Constituent.Uploaded.Document.ConstituentID' => $constituent_id,
      'Constituent.Uploaded.Document.ContentType' => $content_type,
      'Constituent.Uploaded.Document.FileName' => $file_name,
      'Constituent.Uploaded.Document.FileContents' => $contents);

    $doc_id = $poster->add('Constituent.Uploaded.Document', 0, $data)->process(array('AUDIT_LOG' => false, 'target' => 'additional'))->getID();

    return $doc_id;
    /*
    return $this->db->db_insert('CONS_DOCUMENTS', array('target' => 'additional'))->fields(array(
      'CONSTITUENT_ID' => $constituent_id,
      'CONTENT_TYPE' => $content_type,
      'FILE_NAME' => $file_name,
      'CONTENTS' => $contents,
      'CREATED_USERSTAMP' => $this->session->get('user_id'),
      'CREATED_TIMESTAMP' => date('Y-m-d H:i:s')
    ))->execute();
    */
  }

  public function getFile($document_id) {

    $doc = $this->db->db_select('CONS_DOCUMENTS', 'docs', array('target' => 'additional'))
      ->fields('docs', array('CONTENT_TYPE', 'FILE_NAME', 'FILE_CONTENTS'))
      ->condition('CONSTITUENT_DOCUMENT_ID', $document_id)
      ->execute()->fetch();

    return $doc;
  }

  public function removeDocument($document_id) {

    return $this->db->db_delete('CONS_DOCUMENTS', array('target' => 'additional'))->condition('CONSTITUENT_DOCUMENT_ID', $document_id)->execute();

  }
  
}