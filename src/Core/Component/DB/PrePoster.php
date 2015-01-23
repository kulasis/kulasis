<?php

namespace Kula\Core\Component\DB;

class PrePoster implements \Iterator {
  
  private $records;
  private $position;
  
  public function __construct() {
    $this->position = 0;
  }

  public function rewind() {
    $this->position = 0;
  }

  public function current() {
    return $this->records[$this->position];
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    ++$this->position;
  }

  public function valid() {
    return isset($this->records[$this->position]);
  }
  
  public function load($form, $checkField = null) {
    if (count($form) > 0) {
      foreach($form as $table => $table_row) {
        foreach($table_row as $table_id => $row) {
          if ($checkField === null OR (isset($row[$checkField]) AND trim($row[$checkField]) != '')) {
            $this->records[] = new PrePosterRecord($table, $table_id, $row);
          }
        }
      }
    }
    return $this;
  }
  
}