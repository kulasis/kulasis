<?php

namespace Kula\Core\Bundle\ConstituentBundle\Service;

class ConstituentFileService {
  
  public function __construct($db, $session) {
    $this->db = $db;
    $this->session = $session;
  }
  
  public function addFile($constituent_id, $content_type, $file_name, $contents) {

    return $this->db->db_insert('CONS_DOCUMENTS', array('target' => 'additional'))->fields(array(
      'CONSTITUENT_ID' => $constituent_id,
      'CONTENT_TYPE' => $content_type,
      'FILE_NAME' => $file_name,
      'CONTENTS' => base64_encode($contents),
      'CREATED_USERSTAMP' => $this->session->get('user_id'),
      'CREATED_TIMESTAMP' => date('Y-m-d H:i:s')
    ))->execute();

  }

  public function getFile($document_id) {

    $doc = $this->db->db_select('CONS_DOCUMENTS', 'docs', array('target' => 'additional'))
      ->fields('docs', array('CONTENT_TYPE', 'FILE_NAME', 'CONTENTS'))
      ->condition('CONSTITUENT_DOCUMENT_ID', $document_id)
      ->execute()->fetch();

    $doc['CONTENTS'] = base64_decode($doc['CONTENTS']);

    return $doc;
  }

  public function removeDocument($document_id) {

    return $this->db->db_delete('CONS_DOCUMENTS', array('target' => 'additional'))->condition('CONSTITUENT_DOCUMENT_ID', $document_id)->execute();

  }
  
}