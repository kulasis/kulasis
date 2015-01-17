<?php

namespace Kula\Core\Component\Twig\Form;

class Hidden extends Field {
  
  public function html() {
  
    $html = '<input type="hidden"';
    $html .= $this->attributesToHTML();
    $html .= ' />';
    
    return $html;
  }
  
  
}