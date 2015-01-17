<?php

namespace Kula\Core\Component\Twig\Form;

class PasswordField extends Field {
  
  public function html() {
  
    $html = '<input type="password"';
    $html .= $this->attributesToHTML();
    $html .= ' />';
    
    return $html;
  }
  
  
}