<?php

namespace Kula\Core\Component\Focus;

class OrganizationUnit {
  
  private $id;
  private $name;
  private $abbreviation;
  private $type;
  
  private $parent;
  private $children = array();
  
  private $terms = array();
  
  public function __construct($id, $name, $abbreviation, $type, $parent = null) {
    
    $this->id = $id;
    $this->name = $name;
    $this->abbreviation = $abbreviation;
    $this->type = $type;
    $this->parent = $parent;
    
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getID() {
    return $this->id;
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
  
}