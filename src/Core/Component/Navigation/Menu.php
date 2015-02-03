<?php

namespace Kula\Core\Component\Navigation;

class Menu extends Item {
  
  private $dividerBefore;
  private $recordLoaded;
  private $confirmationMessage;

  public function __construct($name, $type, $parent, $db_id, $portal, $sort, $displayName, $route, $dividerBefore, $recordLoaded, $confirmationMessage) {
    
    parent::__construct($name, $parent, $db_id, $portal, $sort, $displayName, $route);
    
    $this->dividerBefore = $dividerBefore;
    $this->recordLoaded = $recordLoaded;
    $this->confirmationMessage = $confirmationMessage;
    
    $this->type = $type;
  }
  
  public function getDividerBefore() {
    if ($this->dividerBefore == 1)
      return true;
  }
  
  public function getRecordLoaded() {
    if ($this->recordLoaded == 1)
      return true;
  }
  
  public function getConfirmationMessage() {
    return $this->confirmationMessage;
  }
  
  public function getType() {
    return $this->type;
  }
  
}