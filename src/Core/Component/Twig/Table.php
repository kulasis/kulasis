<?php

namespace Kula\Core\Component\Twig;

class Table {
  
  public static function header($name, $rows, $hidden, $options = array()) {

    $default_options['class'] = 'data-table';
    
    $html = '<div class="data-table-header-div">'. $name;
    if (isset($options['input']) && $options['input'] === true) {
      $html .= '<span style="float:right;margin-top:-3px;"><button class="data-table-button-add" style="width:100px;" data-table="panel_panel_num_' .  $options['id'] .'">Add</button></span>';
    }
    $html .= '</div>
<table id="panel_panel_num_' . $options['id'] .'" ' . self::integrateOptions($options, $default_options) . '>';
    $html .= '<thead><tr>';
    $html .= '<th class="table-cell-row-header">#</th>';
    if ($rows) {
      foreach ($rows as $row) {
        $html .= self::headerCell($row['db_table'], $row['db_field']);
      }
    }
    $html .= '<th class="table-cell-row-header">X</th>';
    $html .= '</tr></thead>';
    $html .= '<tr class="table-row-new" style="display:none;"><td class="table-cell-row-num">#</td>';
    if ($rows) {
      foreach ($rows as $row) {
        $html .= self::cellInput($row['db_table'], $row['db_field'], 'new_num');
      }
    }
    $html .= '<td class="table-cell-row-delete">';
    
    if ($hidden) {
      foreach ($hidden as $row) {
        $html .= Field::field($row['db_table'], $row['db_field'], 'new_num', $row['value'], 'Y', $options);
      }
    }
    
    $html .= '<input type="checkbox" class="form-delete-checkbox-add" /></td>';
    $html .= '</tr>';
    return $html;
  }
  
  public static function footer() {
    return '</table>';
  }
  
  public static function openTBody($options = array()) {
    return '<tbody>';
  }
  
  public static function closeTBody($options = array()) {
    return '</tbody>';
  }
  
  public static function rowForm($row_num, $rows, $options = array()) {
    $html = '<tr' . self::integrateOptions($options).'>';
    $html .= '<td class="table-cell-row-num">' . $row_num . '</td>';
    if ($rows) {
      foreach ($rows as $row) {
        $html .= self::cellInput($row['db_table'], $row['db_field'], $row['db_row_id'], $row['value']);
      }
    }
    $html .= '<td class="table-cell-row-delete"><input type="checkbox" name="form[' . $row['db_table'] . '][' . $row['db_row_id'] . '][delete_row]" class="form-delete-checkbox" value="Y" /></td>';
    $html .= '</tr>';
    
    return $html;
    
  }
  
  private static function headerCell($db_table, $db_field, $options = array()) {

    $schema = \Kula\Component\Schema\Schema::getSchemaObject();
    
    $field = $schema[$db_table][$db_field];
    
    return '<th' . self::integrateOptions($options).'>' . $field['DISPLAY_NAME'] . '</th>';  
  }
  
  public static function cell($value, $options = array()) {
    return '<td' . self::integrateOptions($options).'>' . $value . '</td>';  
  }
  
  private static function cellInput($db_table, $db_field, $db_row_id = null, $value = null, $options = array()) {
    $schema = \Kula\Component\Schema\Schema::getSchemaObject();
    
    $field = $schema[$db_table][$db_field];
    
    $options['class'] = 'table-cell-input';
    $options['size'] = $field['DISPLAY_SIZE'];
    
    $html = '<td>' . Field::field($db_table, $db_field, $db_row_id, $value, null, $options) . '</td>';
    
    return $html;
  }
  
  private static function integrateOptions($additional_options, $default_options = array()) {
    
    $options = array_merge($default_options, $additional_options);
    
    $options_string = '';
    
    foreach($options as $option_key => $option_value) {
      
      $options_string .= ' ' . $option_key . '="' . $option_value . '"';
      
    }
    
    return $options_string;
    
  }
  
}