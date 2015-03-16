<?php

namespace Kula\Core\Component\Focus;

class OrganizationUnit {
  
  private $id;
  private $name;
  private $abbreviation;
  private $type;
  private $target;
  
  private $parent;
  private $children = array();
  
  private $terms = array();
  
  public function __construct($id, $name, $abbreviation, $type, $target, $parent = null) {
    
    $this->id = $id;
    $this->name = $name;
    $this->abbreviation = $abbreviation;
    $this->type = $type;
    $this->parent = $parent;
    $this->target = $target;
    
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getType() {
    return $this->type;
  }
  
  public function getTarget() {
    return $this->target;
  }
  
  public function addChild($child) {
    $this->children[$child->getID()] = $child;
  }
  
  public function addTerm($organizationTermID, $termID) {
    $this->terms[$termID] = $organizationTermID;
  }
  
  public function getChildren() {
    return $this->children;
  }
  
  public function getTermIDs() {
    return array_keys($this->terms);
  }
  
  public function getOrganizationTermID($termID) {
    return $this->terms[$termID];
  }
  
}