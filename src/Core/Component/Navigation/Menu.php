<?php

namespace Kula\Core\Component\Navigation;

class Menu extends Item {
  
  private $dividerBefore;
  private $recordLoaded;
  private $confirmationMessage;

  public function __construct($name, $parent, $db_id, $portal, $sort, $displayName, $route, $dividerBefore, $recordLoaded, $confirmationMessage) {
    
    parent::__construct($name, $parent, $db_id, $portal, $sort, $displayName, $route);
    
    $this->dividerBefore = $dividerBefore;
    $this->recordLoaded = $recordLoaded;
    $this->confirmationMessage = $confirmationMessage;
    
  }

}