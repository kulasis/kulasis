<?php

namespace Kula\Core\Component\Twig\Form;

class Button extends Field {
  
  public function html() {
  
    $html = '<button ';
    $html .= $this->attributesToHTML();
    $html .= '>';
    $html .= $this->name;
    $html .= '</button>';
    
    return $html;
  }
  
  protected function attributesToHTML() {
    
    // get required attributes
    $attributes = $this->required_attributes;
    
    // mix in default attributes
    $attributes += $this->attributes;
    
    $field = '';
    
    if ($attributes) {
      foreach($attributes as $attribute_key => $attribute_value) {
        $field .= ' ' . $attribute_key . '="' . $attribute_value . '"';
      }
    }
    
    return $field;
    
  }
  
}