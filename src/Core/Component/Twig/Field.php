<?php

namespace Kula\Core\Component\Twig;

use Kula\Core\Component\Permission\Permission;

class Field {
  
  private static $permission;
  private static $focus;
  private static $record;
  private static $poster;
  private static $schema;
  private static $db;
  private static $session;
  private static $chooser;
  
  public static function setDependencies($permission, $focus, $record, $poster, $schema, $db, $session, $chooser) {
    self::$permission = $permission;
    self::$focus = $focus;
    self::$record = $record;
    self::$poster = $poster;
    self::$schema = $schema;
    self::$db = $db;
    self::$session = $session;
    self::$chooser = $chooser;
  }
  
  public static function fieldName($param = array()) {
    $default_param = array('db_table' => '', 'db_field' => '', 'add' => false, 'edit' => false, 'delete' => false, 'prepend_html' => '', 'append_html' => '', 'school_term_only' => false);
    $param = array_merge($default_param, $param);
    
    $html = '';
    
    $org_term_ids = self::$focus->getOrganizationTermIDs();
    
    if (!$param['school_term_only'] OR ($param['school_term_only'] AND count($org_term_ids) == 1)) {
    
    if ($param['delete']) {
      $field = self::getFieldInfo($param['field']);
      if (self::$permission->getPermissionForSchemaObject($field->getTable()->getDBName(), null, Permission::DELETE)) {
        if (isset($param['field_name_override'])) {
          $html = $param['field_name_override'];
        } else {
          $html = 'X';
        }
      
        if ($param['prepend_html'] AND $html != '')
          $html = $param['prepend_html'] . $html;
        if ($param['append_html'] AND $html != '')
          $html = $html . $param['append_html'];
      }
    } elseif (self::_displayValue($param)) {
      $field = self::getFieldInfo($param['field']);
      
      if (isset($param['field_name_override'])) {
        $html = $param['field_name_override'];
      } else {
        $html = $field->getLabelName();
      }
      
      if ($param['prepend_html'] AND $html != '')
        $html = $param['prepend_html'] . $html;
      if ($param['append_html'] AND $html != '')
        $html = $html . $param['append_html'];
    }
    
    
    
    } // end org term if
    
    return $html;
  }
  
  public static function addButton($data_table_name, $db_table, $param = array()) {
    $default_param = array('school_term_only' => false);
    $param = array_merge($default_param, $param);
    
    $org_term_ids = self::$focus->getOrganizationTermIDs();
    
    $record_object = self::$record;
    if (self::$permission->getPermissionForSchemaObject($db_table, null, Permission::ADD) AND
        (!isset($record_object) OR $record_object->getSubmitMode() == 'edit') AND 
      (!$param['school_term_only'] OR ($param['school_term_only'] AND count($org_term_ids) == 1))    )
    return GenericField::button(array('name' => 'Add', 'attributes_html' => array('class' => 'data-table-button-add', 'data-table' => $data_table_name)));
  }
  
  public static function displayHTML($param = array()) {
    $default_param = 
      array('db_table' => '', // db table
            'db_field' => '',  // db field
            'add' => false, // boolean for add
            'edit' => false, // boolean for edit
            'delete' => false, // boolean for delete
            'hidden' => false, // field will be made hidden
            'prepend_html' => '', // HTML before field HTML
            'append_html' => '',  // HTML after field HTML
          ); 
    // merge passed in parameters with default parameters
    $param = array_merge($default_param, $param);
    
    $html = '';
    if (
         ($param['delete'] AND self::$permission->getPermissionForSchemaObject($param['db_table'], null, Permission::DELETE)) OR
         ($param['add'] AND self::$permission->getPermissionForSchemaObject($param['db_table'], null, Permission::ADD))
        ) {
      if ($param['prepend_html'])
        $html = $param['prepend_html'] . $html;
      if ($param['append_html'])
        $html = $html . $param['append_html'];
    }
    
    return $html;
  }
    
  /**
   *  $options list
   *  DB_TABLE = database table name
   *  DB_FIELD = database field name
   *  DB_ROW_ID = row in database table
   *  VALUE = value to display in field
   *  NEW = BOOLEAN template field for new rows
   *  LABEL = name of label to show
   *  DELETE = BOOLEAN checkbox to flag record for deletion
   *  ATTRIBUTES = default attributes to pass to field
   *  HIDDEN = regardless of set field type, name and value should be used in hidden field
   */
  public static function field($param = array()) {
    $default_param = 
      array('field' => '', // db table
            'db_row_id' => '', // db row ID to update
            'table_row' => true, // adding to a table row 
            'value' => '', // current value for field
            'value_to_compare' => '', // for radio buttons
            'add' => false, // boolean for add
            'edit' => false, // boolean for edit
            'delete' => false, // boolean for delete
            'non' => false, // boolean for field data just needed for processing
            'post_type' => false, // string for post type
            'search' => false, // boolean for searching only field
            'label' => false,  // display label with field
            'hidden' => false, // field will be made hidden
            'prepend_html' => '', // HTML before field HTML
            'attributes_html' => array(), // HTML attributes to be mixed-in with field
            'append_html' => '',  // HTML after field HTML
            'confirmation_field' => false, // if field is a confirmation field
            'lookup' => 'D', // display settings for lookup: C = Code only, D = Description only, CD = Code - Description
            'input' => true, // display value only
            'school_term_only' => false, // attribute can only be edited when focused to a school
            'report' => false, // field is a report parameter
          ); 
    // merge passed in parameters with default parameters
    $param = array_merge($default_param, $param);
    
    // get schema field info
    $field = self::getFieldInfo($param['field']);
    
    $field_name = self::getNameForField($param); // to delete
    
    // if failed, swap value for posted value
    $poster_object = self::$poster;
    /*
    if (isset($poster_object) AND $poster_object->hasViolations()) {
      $posted_value = $poster_object->getPostedValue($field_name);
      if (!is_array($posted_value)) {
        $param['value'] = $posted_value;
        $param['attributes_html']['class'] = $param['attributes_html']['class'] . ' field_error';
      }
    }
    */
    
    // hidden flag takes presidence
    if ($param['hidden']) {
      return GenericField::hidden($field_name, $param['value'], $param['attributes_html']);
    }
    
    $html = '';
    
    if ($param['add'] AND !self::$permission->getPermissionForSchemaObject($field->getTable()->getDBName(), null, Permission::ADD)) {
      return null;
    }
    
    // generate checkboxes for deleting
    $org_term_ids = self::$focus->getOrganizationTermIDs();
    if ($param['delete'] AND 
    self::$permission->getPermissionForSchemaObject($field->getTable()->getDBName(), null, Permission::DELETE) AND 
    (!$param['school_term_only'] OR ($param['school_term_only'] AND count($org_term_ids) == 1))) {
      
      if ($param['add'] AND self::$permission->getPermissionForSchemaObject($field->getTable()->getDBName(), null, Permission::ADD))
        $param['attributes_html']['class'] = 'form-delete-checkbox-add';
      else   
        $param['attributes_html']['class'] = 'form-delete-checkbox';
      
      $html = GenericField::checkbox($field_name, 'Y', $param['attributes_html']);
      
      if ($param['prepend_html'])
        $html = $param['prepend_html'] . $html;
      if ($param['append_html'])
        $html = $html . $param['append_html'];
      
      return $html;
    } elseif ($param['delete']) {
      return null;
    }
    
    // generate text field
    if ($field->getFieldType()  == 'text') {
      $html .= self::_textField($param);
    }
    
    if ($field->getFieldType() == 'textarea') {
      $html .= self::_textArea($param);
    }
    
    if ($field->getFieldType() == 'password') {
      $html .= self::_password($param);
    }
    
    if ($field->getFieldType() == 'date') {
      $html .= self::_date($param);
    }
    
    if ($field->getFieldType() == 'time') {
      $html .= self::_time($param);
    }
    
    if ($field->getFieldType() == 'checkbox') {
      $html .= self::_checkbox($param);
    }
    
    if ($field->getFieldType() == 'multicheckbox') {
      $html .= self::_multipleCheckbox($param);
    }
    
    if ($field->getFieldType() == 'radio') {
      $html .= self::_radio($param);
    }
    
    if ($field->getFieldType() == 'lookup') {
      $html .= self::_lookup($param);
    }
    
    if ($field->getFieldType() == 'chooser') {
      $html .= self::_chooser($param);
    }
    
    if ($field->getFieldType() == 'select') {
      $html .= self::_select($param);
    }
    
    if ($field->getFieldType() == '') {
      $html .= $param['value'];
    }
    
    // if label set wrap in label
    if ($param['label']) {
      $original_html = $html;
      $html = '<div class="form-field">';
      $html .= self::_label($param);
      $html .= $original_html;
      /*
      if (self::_displayField($param)) {
        $html .= $original_html;
      } else {
        $html .= '<span style="display:table-cell;padding-top:0.5em;padding-bottom:0.5em;font-weight:bold;padding-left:3px;width:30px;">' . $original_html . '</span>';
      }
      */
      $html .= '</div>';
    }
    
    if ($param['prepend_html'] AND (self::_displayField($param) OR self::_displayValue($param)))
      $html = $param['prepend_html'] . $html;
    if ($param['append_html'] AND (self::_displayField($param) OR self::_displayValue($param)))
      $html = $html . $param['append_html'];
    
    return $html;
  }
  
  /* PRIVATE FIELD METHODS */  
  
  private static function _textField($param) {

    if (self::_displayField($param)) {
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      $param['attributes_html']['size'] = $schema->getFieldSize();
      $class = $schema->getClass();
      if ($class) {
        $param['value'] = $class::calculate($param['value']);
      }
      return GenericField::text($field_name, $param['value'], $param['attributes_html']);
    } elseif (self::_displayValue($param) AND !$param['input']) {
      $schema = self::getFieldInfo($param['field']);
      $class = $schema->getClass();
      if ($class) {
        $param['value'] = $class::calculate($param['value']);
      }
      return $param['value'];
    } elseif (self::_displayValue($param)) {
      $schema = self::getFieldInfo($param['field']);
      $param['attributes_html']['size'] = $schema->getFieldSize();
      $param['attributes_html']['disabled'] = 'disabled';
      $class = $schema->getClass();
      if ($class) {
        $param['value'] = $class::calculate($param['value']);
      }
      return GenericField::text(null, $param['value'], $param['attributes_html']);
    }
  }
  
  private static function _textArea($param) {

    if (self::_displayField($param)) {
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      $param['attributes_html']['cols'] = $schema->getFieldSize();
      if (!isset($param['attributes_html']['rows'])) $param['attributes_html']['rows'] = 5;
      return GenericField::textArea($field_name, $param['value'], $param['attributes_html']);
    } elseif (self::_displayValue($param) AND !$param['input']) {
      return $param['value'];
    } elseif (self::_displayValue($param)) {
      $schema = self::getFieldInfo($param['field']);
      $param['attributes_html']['cols'] = $schema->getFieldSize();
      $param['attributes_html']['disabled'] = 'disabled';
      if (!isset($param['attributes_html']['rows'])) $param['attributes_html']['rows'] = 5;
      return GenericField::textArea(null, $param['value'], $param['attributes_html']);
    }
  }
  
  private static function _password($param) {
    if (self::_displayField($param)) {
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      $param['attributes_html']['size'] = $schema->getFieldSize();
      return GenericField::password($field_name, $param['value'], $param['attributes_html']);
    } 
  }
  
  private static function _date($param) {
    if ($param['value']) {
      if (isset($param['date_format']))
        $date_format = $param['date_format'];
      else
        $date_format = 'm/d/Y';
      $param['value'] = date($date_format, strtotime($param['value']));
    }
    if (self::_displayField($param)) {
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      if ($schema->getFieldSize())
        $param['attributes_html']['size'] = $schema->getFieldSize();
      else
        $param['attributes_html']['size'] = 13;
      return GenericField::text($field_name, $param['value'], $param['attributes_html']);
    } elseif (self::_displayValue($param) AND !$param['input']) {
      return $param['value'];
    } else {
      $schema = self::getFieldInfo($param['field']);
      if ($schema->getFieldSize())
        $param['attributes_html']['size'] = $schema->getFieldSize();
      else
        $param['attributes_html']['size'] = 13;
      $param['attributes_html']['disabled'] = 'disabled';
      return GenericField::text(null, $param['value'], $param['attributes_html']);
    }  
  }
  
  private static function _time($param) {
    if ($param['value']) {
      if (isset($param['date_format']))
        $date_format = $param['date_format'];
      else
        $date_format = 'g:i A';
      $param['value'] = date($date_format, strtotime($param['value']));
    }
    if (self::_displayField($param)) {
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      if ($schema->getFieldSize())
        $param['attributes_html']['size'] = $schema->getFieldSize();
      else
        $param['attributes_html']['size'] = 13;
      return GenericField::text($field_name, $param['value'], $param['attributes_html']);
    } elseif (self::_displayValue($param) AND !$param['input']) {
      return $param['value'];
    } else {
      $schema = self::getFieldInfo($param['field']);
      if ($schema->getFieldSize())
        $param['attributes_html']['size'] = $schema->getFieldSize();
      else
        $param['attributes_html']['size'] = 13;
      $param['attributes_html']['disabled'] = 'disabled';
      return GenericField::text(null, $param['value'], $param['attributes_html']);
    }  
  }
  
  private static function _multipleCheckbox($param) {
    $html = '';
    $schema = self::getFieldInfo($param['field']);
    $field_name = self::getNameForField($param) . '[]';
    
    $html .= GenericField::checkbox($field_name, $param['value'], $param['attributes_html']);
    return $html;
  }
  
  private static function _checkbox($param) {
    if (self::_displayField($param)) {
      $html = '';
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      
      $record_object = self::$record;
      if (isset($record_object) AND $record_object->getSubmitMode() == 'search') {
        $html .= '';
      } else {
        $field_name_for_hidden = $field_name . '[checkbox_hidden]';  
        $html .= GenericField::hidden($field_name_for_hidden, $param['value'], $param['attributes_html']);
      }
      $field_name = $field_name . '[checkbox]';
      if ($param['value'] == 'Y')
        $param['attributes_html']['checked'] = 'checked';
      $html .= GenericField::checkbox($field_name, null, $param['attributes_html']);
      return $html;
    } elseif (self::_displayValue($param)) {
      return $param['value'];
    }
  }
  
  private static function _radio($param) {
    if (self::_displayField($param)) {
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      if ($param['value'] == $param['value_to_compare']) $param['attributes_html']['checked'] = 'checked';
      return GenericField::radio($field_name, $param['value'], $param['attributes_html']);
    } elseif (self::_displayValue($param)) {
      return '';
    }
  }
  
  private static function _lookup($param) {
    $schema = self::getFieldInfo($param['field']);
    $lookup = array('' => '');
    
    if (self::_displayField($param)) {
      $field_name = self::getNameForField($param);
      $lookup += \Kula\Component\Lookup\Lookup::getLookupMenu($schema['LOOKUP_ID'], $param['lookup']);
      return GenericField::select($lookup, $field_name, $param['value'], $param['attributes_html']);  
    } elseif (self::_displayValue($param) AND !$param['input']) {
      return \Kula\Component\Lookup\Lookup::getLookupValue($schema['LOOKUP_ID'], $param['value'], $param['lookup'], true);
    } elseif (self::_displayValue($param)) {
      $field_name = self::getNameForField($param);
      $lookup += \Kula\Component\Lookup\Lookup::getLookupMenu($schema['LOOKUP_ID'], $param['lookup'], true);
      $param['attributes_html']['disabled'] = 'disabled';
      return GenericField::select($lookup, $field_name, $param['value'], $param['attributes_html']);  
    }
  }
  
  private static function _chooser($param) {
    $schema = self::getFieldInfo($param['field']);
    $field_name = self::getNameForField($param);
    $chooser = self::$chooser->get($schema->getChooser());
    $current_choice_menu = '';
    
    if (self::_displayField($param)) {
      $data = array();
      $chooser_search_route = $chooser->searchRoute();
      if (isset($param['attributes_html']['class'])) {
        $param['attributes_html']['class'] = $param['attributes_html']['class'] . ' chooser-search';
      } else {
        $param['attributes_html']['class'] = 'chooser-search';  
      }
      $container = $GLOBALS['kernel']->getContainer();
      $data = array('data-search-url' => $container->get('router')->generate($chooser_search_route));
      $current_choice_menu = $chooser->choice($param['value']);

      $params_text_field = array_merge($param['attributes_html'], $data, array('style' => 'display:none;', 'size' => '10'));
      $html = '';
      $html .= GenericField::text($field_name . '[chooser]', null, $params_text_field);
      $html .= GenericField::select($current_choice_menu, $field_name . '[value]', $param['value'], $param['attributes_html']);
      return $html;
    } elseif (self::_displayValue($param)) {
      $html = '';
      if ($chooser) {
        $current_choice_menu = $chooser->choice($param['value']);
        $html .= $current_choice_menu[$param['value']];
      } else {
        $html .= $param['value'];
      }
      return $html;
    }
  }
  
  private static function _select($param) {
    if (self::_displayField($param)) {
      $schema = self::getFieldInfo($param['field']);
      $field_name = self::getNameForField($param);
      $select_options = array('' => '');
      // check if field is calculated
      if (isset($schema['CALCULATED_FIELD_LOGIC_CLASS'])) {
        $class = $schema['CALCULATED_FIELD_LOGIC_CLASS'];
        $select_options += $class::select($schema, $param);
      }
      return GenericField::select($select_options, $field_name, $param['value'], $param['attributes_html']);
    } elseif (self::_displayValue($param) AND !$param['input']) {
      $schema = self::getFieldInfo($param['field']);
      $select_options = array('' => '');
      // check if field is calculated
      if (isset($schema['CALCULATED_FIELD_LOGIC_CLASS'])) {
        $param['attributes_html']['disabled'] = 'disabled';
        $class = $schema['CALCULATED_FIELD_LOGIC_CLASS'];
        $select_options += $class::select($schema, $param);
      }
      return $select_options[$param['value']];
    } elseif (self::_displayValue($param)) {
      $schema = self::getFieldInfo($param['field']);
      $select_options = array('' => '');
      // check if field is calculated
      if (isset($schema['CALCULATED_FIELD_LOGIC_CLASS'])) {
        $param['attributes_html']['disabled'] = 'disabled';
        $class = $schema['CALCULATED_FIELD_LOGIC_CLASS'];
        $select_options += $class::select($schema, $param);
      }
      return GenericField::select($select_options, null, $param['value'], $param['attributes_html']);
    }
  }
  
  private static function _label($param) {
    
    if (self::_displayField($param) || self::_displayValue($param)) {
      if (isset($param['field_name_override'])) {
        $label_name = $param['field_name_override'];
      } else {
        $schema = self::getFieldInfo($param['field']);
        $label_name = $schema->getLabelName();
      }
      return GenericField::label($label_name);
    } 
  }
  
  /* HELPER METHODS */
  
  private static function getNameForField($param) {
    
    $schema = self::getFieldInfo($param['field']);
    $db_action = '';
    if ($param['add'])
      $db_action = 'add';
    if ($param['delete'])
      $db_action = 'delete';
    if ($param['edit'])
      $db_action = 'edit';
    if ($param['non'])
      $db_action = 'non';
    if ($param['search'])
      $db_action = 'search';
    if ($param['post_type'])
      $db_action = $param['post_type'];
      
    // if controller set to search mode, set db_action to search
    $record_object = self::$record;
    if (isset($record_object) AND $record_object->getSubmitMode() == 'search' AND !$param['add']) {
      $db_action = 'search';
    }
    
    
    if ($schema->getFieldType()  == 'CALCULATED') {
      $class = $schema['CALCULATED_FIELD_LOGIC_CLASS'];
      $name = self::$schema->getFieldNameForRowID($param['db_table'], $schema['CALCULATED_FIELD_UPDATE_FIELD_ID']); 
    }
    
    
    if (isset($record_object) AND $record_object->getAddMode()) {
      $field_name = 'add';
    } else {
      $field_name = strtolower($db_action);
    }

    $field_name .= '[' . $schema->getTable()->getName() . ']';
    if ($db_action == 'add' AND $param['table_row']) $field_name .= '[new_num]';
    if ($db_action == 'add' AND !$param['table_row'] AND !isset($param['add_remove'])) $field_name .= '[new]';
    if ($db_action == 'edit' AND isset($record_object) AND $record_object->getAddMode()) $field_name .= '[0]'; 
    if ($param['db_row_id']) $field_name .= '[' . $param['db_row_id'] . ']';
    //if ($param['hidden']) $field_name .= '[hidden]';
    if ($schema->getFieldType() == 'CALCULATED')
      $field_name .= '[' . $name. ']';
    elseif ($db_action == 'delete') {
      $field_name .= '[delete_row]';
    } else {
      if ($param['confirmation_field']) $confirm = '_confirmation'; else $confirm = '';
      $field_name .= '[' . $schema->getName() . $confirm . ']';
    }
    
    return $field_name;
  }
  
  private static function getFieldInfo($field) {
    $schema = self::$schema->getField($field);
    
    //if (!isset($schema[$db_table][$db_field]))
    //  throw new \Exception('Field ' . $db_field . ' does not exist in table ' . $db_table . ' in Kula SIS Schema.');
    
    return $schema;
  }
  
  private static function _getSubmitMode() {
    $record_obj = self::$record;
    if ($record_obj)
      return $record_obj->getSubmitMode();
  }
  
  private static function _displayField($param) {
    $org_term_ids = self::$focus->getOrganizationTermIDs();
    
    $schema = self::getFieldInfo($param['field']);
    if (
         (
          ($param['add'] OR $param['edit'] OR $param['non']) AND 
            (!$param['school_term_only'] OR ($param['school_term_only'] AND count($org_term_ids) == 1)) AND 
            self::$permission->getPermissionForSchemaObject($schema->getTable()->getDBName(), $schema->getDBName(), Permission::WRITE)
              
        )
         OR
        (
          ($param['search'] OR self::_getSubmitMode() == 'search' OR $param['report']) AND 
          self::$permission->getPermissionForSchemaObject($schema->getTable()->getDBName(), $schema->getDBName(), Permission::READ)
        )
      ) {
          return true;
    }      
  }
  
  private static function _displayValue($param) {
    $schema = self::getFieldInfo($param['field']);
    if (self::$permission->getPermissionForSchemaObject($schema->getTable()->getDBName(), $schema->getDBName(), Permission::READ)
        ) {
          return true;
    }      
  }
  
}