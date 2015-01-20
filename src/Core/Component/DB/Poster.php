<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\DB\DB as DB;
use Kula\Core\Component\Schema\Schema as Schema;
use Symfony\Component\HttpFoundation\RequestStack as RequestStack;
use Kula\Core\Component\DB\PosterRecord as PosterRecord;

class Poster {
  
  private $db;
  private $requestStack;
  private $schema;
  
  private $records = array();
  
  private $result;
  private $primary_keys;
  
  private $original_data;
  
  private $hasViolations = false;
  private $isPosted = false;
  

  public function __construct(DB $db, Schema $schema, RequestStack $requestStack, $session, $permission) {
    $this->db = $db;
    $this->requestStack = $requestStack;
    $this->schema = $schema;
    $this->session = $session;
    $this->permission = $permission;
    
    $this->result = null;
    $this->hasViolations = false;
    $this->isPosted = false;
  }
  
  public function add($table, $id, $fields) {
    $this->records[$table][$id] = new PosterRecord($this->db, $this->schema, $this->session, $this->permission, PosterRecord::ADD, $table, $id, $fields);
  }
  
  public function addMultiple(array $post) {
    ksort($post);
    foreach($post as $table => $tableRow) {
      foreach($tableRow as $id => $row) {
        if ($id !== 'new_num') { 
          $this->add($table, $id, $row);
        }
      }
    }
  }
  
  public function edit($table, $id, $fields) {
    $this->records[$table][$id] = new PosterRecord($this->db, $this->schema, $this->session, $this->permission, PosterRecord::EDIT, $table, $id, $fields);
  }
  
  public function editMultiple(array $post) {
    ksort($post);
    foreach($post as $table => $tableRow) {
      foreach($tableRow as $id => $row) {
        $this->edit($table, $id, $row);
      }
    }
  }
  
  public function delete($table, $id, $fields) {
    $this->records[$table][$id] = new PosterRecord($this->db, $this->schema, $this->session, $this->permission, PosterRecord::DELETE, $table, $id, $fields);
  }
  
  public function deleteMultiple(array $post) {
    ksort($post);
    foreach($post as $table => $tableRow) {
      foreach($tableRow as $id => $row) {
        $this->delete($table, $id, $row);
      }
    }
  }
  
  public function process() {
    if (count($this->records) > 0) {
      $transaction = $this->db->db_transaction('poster');
      try {
        foreach($this->records as $table => $tableRow) {
          foreach($tableRow as $id => $record) {
            $record->process();
          }
        }
      } catch (Exception $e) {
        $transaction->rollback();
      }
    }
  }
  
  public function getPosterRecord($table, $id) {
    return $this->records[$table][$id];
  }
  
}