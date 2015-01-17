<?php

namespace Kula\Core\Component\Twig\Form;

class TextField extends Field {
  
  public function html() {
  
    $html = '<input type="text"';
    $html .= $this->attributesToHTML();
    $html .= ' />';
    
    return $html;
  }
  
  
}