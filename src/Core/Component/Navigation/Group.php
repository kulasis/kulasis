<?php

namespace Kula\Core\Component\Navigation;

class Group extends Item {

  private $forms = array();
  private $reports = array();

  public function addForm(Form $form) {
    $this->forms[] = $form;
  }

  public function getForms() {
    return $this->forms;
  }
  
  public function addReport(Report $report) {
    $this->reports[] = $report;
  }
  
  public function getReports() {
    return $this->reports;
  }
  
  public function getType() {
    return 'group';
  }

}