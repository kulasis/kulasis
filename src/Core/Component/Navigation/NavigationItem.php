<?php

namespace Kula\Core\Component\Navigation;

class NavigationItem {
  
  private $parent;
  
  private $name;
  private $db_id;
  private $type;
  private $portal;
  private $sort;
  private $displayName;
  private $dividerBefore;
  private $recordLoaded;
  private $route;
  private $confirmationMessage;
  
  public function __construct($name, $parent, $db_id, $type, $portal, $sort, $displayName, $dividerBefore, $recordLoaded, $route, $confirmationMessage) {
    
    $this->name = $name;
    $this->parent = $parent;
    $this->db_id = $db_id;
    $this->type = $type;
    $this->portal = $portal;
    $this->sort = $sort;
    $this->displayName = $displayName;
    $this->dividerBefore = $dividerBefore;
    $this->recordLoaded = $recordLoaded;
    $this->route = $route;
    $this->confirmationMessage = $confirmationMessage;
    
  }
  
}