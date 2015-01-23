<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\DB\DB as DB;
use Kula\Core\Component\Schema\Schema as Schema;
use Symfony\Component\HttpFoundation\RequestStack as RequestStack;
use Kula\Core\Component\DB\PosterRecord as PosterRecord;

class Poster {
  
  protected $container;
  
  private $db;
  private $requestStack;
  private $schema;
  
  private $records = array();
  
  private $result;
  private $primary_keys;
  
  private $original_data;
  
  private $hasViolations = false;
  private $isPosted = false;
  

  public function __construct($container) {
    
    $this->container = $container;
    $this->db = $this->container->get('kula.core.db');
    $this->requestStack = $this->container->get('request_stack');
    $this->schema = $this->container->get('kula.core.schema');
    $this->session = $this->container->get('kula.core.session');
    $this->permission = $this->container->get('kula.core.permission');
    
    $this->result = null;
    $this->hasViolations = false;
    $this->isPosted = false;
  }
  
  public function add($table, $id, $fields) {
    $this->records[$table][$id] = new PosterRecord($this->container, PosterRecord::ADD, $table, $id, $fields);
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
    $this->records[$table][$id] = new PosterRecord($this->container, PosterRecord::EDIT, $table, $id, $fields);
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
    $this->records[$table][$id] = new PosterRecord($this->container, PosterRecord::DELETE, $table, $id, $fields);
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
      } catch (\PDOException $e) {
        $transaction->rollback();
        throw new \PDOException ($e);
      }
    }
  }
  
  public function getPosterRecord($table, $id) {
    return $this->records[$table][$id];
  }
  
}