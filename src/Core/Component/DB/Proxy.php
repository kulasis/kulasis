<?php

namespace Kula\Core\Component\DB;

use Symfony\Component\Yaml\Yaml;

use Kula\Core\Component\Database\Database;
use Kula\Core\Component\Database\Query\Condition;

class Proxy {
  
  protected $db_object;
  
  public function __construct($object) {
    $this->db_object = $object;
  }
  
  public function __call($name, $arguments) {

    $this->db_object = call_user_func_array(array($this->db_object, $name), $arguments);
    
    return $this;
  }
  
  public function setDBObject($object) {
    $this->db_object = $object;
  }
  
  public function execute() {
    return $this->db_object->execute();
  }
  
}