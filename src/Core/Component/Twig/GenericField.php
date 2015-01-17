<?php

namespace Kula\Core\Component\Twig;

class GenericField {  
  
  public static function label($name, $id = null) {
    $field = new \Kula\Core\Component\Twig\Form\Label($name);
    return $field->html();
  }
  
  public static function button($param = array()) {
    $field = new \Kula\Core\Component\Twig\Form\Button($param['name'], null, $param['attributes_html']);
    return $field->html();
  }
  
  public static function checkbox($name, $value, $options) {
    $field = new \Kula\Core\Component\Twig\Form\Checkbox($name, $value, $options);
    return $field->html();
  }

  public static function radio($name, $value, $options) {
    $field = new \Kula\Core\Component\Twig\Form\Radio($name, $value, $options);
    return $field->html();
  }
  
  public static function hidden($name, $value, $options = array()) {
    $field = new \Kula\Core\Component\Twig\Form\Hidden($name, $value, $options);
    return $field->html();
  }
  
  public static function text($name, $value = null, $options = array()) {
    $field = new \Kula\Core\Component\Twig\Form\TextField($name, $value, $options);
    return $field->html();
  }
  
  public static function textArea($name, $value = null, $options = array()) {
    $field = new \Kula\Core\Component\Twig\Form\TextArea($name, $value, $options);
    return $field->html();
  }
  
  public static function password($name, $value = null, $options = array()) {
    $field = new \Kula\Core\Component\Twig\Form\PasswordField($name, $value, $options);
    return $field->html();
  }
  
  public static function select($select_options, $name, $value = null, $options = array()) {
    $field = new \Kula\Core\Component\Twig\Form\Select($name, $select_options, $value, $options);
    return $field->html();
  }
  
}