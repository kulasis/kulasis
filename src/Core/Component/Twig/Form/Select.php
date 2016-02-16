<?php

namespace Kula\Core\Component\Twig\Form;

class Select extends Field {
  
  protected $options;
  
  public function __construct($name, $options = array(), $value = null, $attributes = array(), $required_attributes = array()) {
    parent::__construct($name, $value, $attributes, $required_attributes);

    if (is_array($options)) $this->options = $options;  
  }
  
  public function html() {
  
    $html = '<select ';
    $html .= $this->attributesToHTML();
    $html .= '>';

    $_idx = 0;
    $id = null;
    $class = null;

    if (isset($this->options)) {
      foreach ($this->options as $_key => $_val) {
        $html .= $this->smarty_function_html_options_optoutput($_key, $_val, $this->value, $id, $class, $_idx);
      }
    }
    
    $html .= '</select>';
    
    return $html;
  }
  
  public function smarty_function_html_options_optoutput($key, $value, $selected, $id, $class, &$idx)
  {
      if (!is_array($value)) {
          $_key = $key;
          $_html_result = '<option value="' . $_key . '"';
          if (is_array($selected)) {
              if (isset($selected[$_key])) {
                  $_html_result .= ' selected="selected"';
              }
          } elseif ($_key == $selected) {
              $_html_result .= ' selected="selected"';
          }
          $_html_class = !empty($class) ? ' class="' . $class . ' option"' : '';
          $_html_id = !empty($id) ? ' id="' . $id . '-' . $idx . '"' : '';
          if (is_object($value)) {
              if (method_exists($value, "__toString")) {
                  $value = $value->__toString();
              }
          } else {
              $value = $value;
          }
          $_html_result .= $_html_class . $_html_id . '>' . $value . '</option>' . "\n";
          $idx ++;
      } else {
          $_idx = 0;
          $_html_result = $this->smarty_function_html_options_optgroup($key, $value, $selected, !empty($id) ? ($id . '-' . $idx) : null, $class, $_idx);
          $idx ++;
      }

      return $_html_result;
  }

  public function smarty_function_html_options_optgroup($key, $values, $selected, $id, $class, &$idx)
  {
      $optgroup_html = '<optgroup label="' . $key . '">' . "\n";
      foreach ($values as $key => $value) {
          $optgroup_html .= $this->smarty_function_html_options_optoutput($key, $value, $selected, $id, $class, $idx);
      }
      $optgroup_html .= "</optgroup>\n";

      return $optgroup_html;
  }
  
  
}