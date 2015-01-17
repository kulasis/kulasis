<?php

namespace Kula\Core\Component\Twig\Form;

class TextArea extends Field {
  
  public function html() {
  
    $html = '<textarea';
    $html .= $this->attributesToHTML();
    $html .= '>';
    $html .= $this->value();
    $html .= '</textarea>';
    
    return $html;
  }
  
  protected function attributesToHTML() {
    
    // get required attributes
    $attributes = $this->required_attributes;
    if ($this->name) $attributes['name'] = $this->name;
    
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