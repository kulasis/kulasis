<?php

namespace Kula\Core\Component\Twig\Form;

class Radio extends Field {
  
  public function html() {
    
    $html = '<input type="radio"';
    $html .= $this->attributesToHTML();
    $html .= ' />';
    
    return $html;
  }
  
  protected function attributesToHTML() {
    
    // get required attributes
    $attributes = $this->required_attributes;
    if ($this->name) $attributes['name'] = $this->name;
    if ($this->value) $attributes['value'] = $this->value;
    
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