<?php

namespace Kula\Core\Component\Twig\Form;

class File extends Field {
  
  public function html() {
  
    $html = '<input type="file"';
    $html .= $this->attributesToHTML();
    $html .= ' />';
    
    return $html;
  }
  
  
}