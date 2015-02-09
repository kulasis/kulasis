<?php

namespace Kula\Core\Component\Navigation;

class Group extends Item {

  private $forms = array();
  private $reports = array();

  public function addForm(Form $form) {
    $this->forms[] = $form;
  }

  public function getForms($permission = null) {
    
    $forms = array();

    if ($this->forms) {
      
      foreach($this->forms as $id => $form) {
        if ($permission AND $permission->getPermissionForNavigationObject($form->getName())) {
          $forms[] = $form;
        }
      }
      
    }
    
    return $forms;
  }
  
  public function addReport(Report $report) {
    $this->reports[] = $report;
  }
  
  public function getReports($permission = null) {
    
    $reports = array();

    if ($this->reports) {
      
      foreach($this->reports as $id => $report) {
        if ($permission AND $permission->getPermissionForNavigationObject($report->getName())) {
          $reports[] = $report;
        }
      }
      
    }
    
    return $reports;
  }
  
  public function getType() {
    return 'group';
  }

}