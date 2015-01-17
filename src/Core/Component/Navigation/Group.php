<?php

namespace Kula\Core\Component\Navigation;

class Group extends Item {

  private $forms = array();

  public function addForm(Form $form) {
    $forms[] = $form;
  }

  public function getForms() {
    return $this->forms();
  }

}