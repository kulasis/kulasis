<?php

namespace Kula\Core\Component\Twig\Form;

abstract class Field {
  
  protected $name;
  protected $value;
  protected $attributes = array();
  protected $required_attributes = array();
  
  public function __construct($name, $value = null, $attributes = array(), $required_attributes = array()) {
    $this->setName($name);
    $this->setValue($value);
    if (is_array($attributes)) $this->attributes = $attributes;
    if (is_array($required_attributes)) $this->required_attributes = $required_attributes;
  }
  
  public abstract function html();
  
  public function name() {
    return $this->name;
  }
  
  public function setName($name) {
    $this->name = $name;
  }
  
  public function value() {
    return $this->value;
  }
  
  public function setValue($value) {
    $this->value = $value;
  }
  
  public function setAttribute($key, $value) {
    $this->attributes[$key] = $value;
  }
  
  public function getAttribute($key) {
    if (isset($this->attributes[$key]))
      return $this->attributes[$key];
  }
  
  public function setRequiredAttribute($key, $value) {
    $this->required_attributes[$key] = $value;
  }
  
  protected function attributesToHTML() {
    
    // get required attributes
    $attributes = $this->required_attributes;
    if ($this->name) $attributes['name'] = $this->name;
    if ($this->value !== null) $attributes['value'] = $this->value;
    
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