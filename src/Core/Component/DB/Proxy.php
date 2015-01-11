<?php

namespace Kula\Core\Component\DB;

class Proxy {
  
  protected $db_object;
  
  public function __construct($object) {
    $this->setDBOBject($object);
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
  
  public function __toString() {
    return (string) $this->db_object;
  }
  
}