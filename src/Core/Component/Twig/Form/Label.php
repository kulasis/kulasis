<?php

namespace Kula\Core\Component\Twig\Form;

class Label extends Field {
  
  public function html() {
  
    $html = '<label ';
    $html .= $this->attributesToHTML();
    $html .= '>';
    $html .= $this->name;
    $html .= '</label>';
    
    return $html;
  }
  
  
}