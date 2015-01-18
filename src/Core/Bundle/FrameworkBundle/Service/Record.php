<?php

namespace Kula\Core\Bundle\FrameworkBundle\Service;

use \Kula\Core\Component\Record\RecordDelegateInterface;

class Record {
  
  private $delegate;
  private static $record_object;
  
  private $record_type;
  private $add_mode;
  
  private $id_stack;
  private $selected_record_id;
  private $selected_record;
  
  public function __construct($db, $session, $focus, $request, $flash, $recordType) {
    $this->db = $db;
    $this->session = $session;
    $this->focus = $focus;
    $this->request = $request->getCurrentRequest();
    $this->flash = $flash;
    $this->recordType = $recordType;
  }
  
  public function setRecordType($recordTypeName, $add_mode = null, $eager_search_data = null) {
    $recordType = $this->recordType->getRecordType($this->session->get('portal'), $recordTypeName);
    $delegate = $recordType->getClass();
    
    if (!isset($recordType))
      throw new \Exception('Record type ' . $record_type . ' does not exist in CORE_RECORD_TYPES.');
    
    // instantiate delegate
    $this->delegate = new $delegate($this->db, $this->session, $this->focus);
    
    $this->record_type = $recordType->getName();
    
    if ($this->session->get('portal') == 'sis' OR $this->session->get('portal') == 'core') {
    
      // set if in add mode
      $this->setAddMode($add_mode);
    
      if (!$add_mode) {
      
        // try and set through existing record
        // get selected ID either through POST or GET
        if ($this->request->request->get('record_id')) {
          $this->selected_record_id = $this->request->request->get('record_id');
        } else {
          $this->selected_record_id = $this->request->query->get('record_id');
        }
      
        if ($this->request->request->get('record_type')) {
          $selected_record_type = $this->request->request->get('record_type');
        } else {
          $selected_record_type = $this->request->query->get('record_type');
        }
    
        // set record from id
        // if searching, process search, load first record returned
        if ($this->request->request->get('mode') == 'search' AND $this->selected_record_id == ''
        ) {
          $post_data = \Kula\Component\Database\Searcher::startProcessing();
          $this->selected_record_id = $this->_search($post_data);
        } else {
      
          if (!isset($selected_record_type) || $selected_record_type == $this->record_type) {
            // if looking for next record ID
            if ($this->request->request->get('scrub') == 'next' || $this->request->query->get('scrub') == 'next') {
              $this->selected_record_id = $this->_getNextRecordID();
            // if looking for previous record ID
            } elseif ($this->request->request->get('scrub') == 'previous' || $this->request->query->get('scrub') == 'previous') {
              $this->selected_record_id = $this->_getPreviousRecordID();
            }
          } elseif ($selected_record_type != $this->record_type) {
            if (method_exists($this->delegate, 'getFromDifferentType')) {
              $this->selected_record_id = $this->delegate->getFromDifferentType($selected_record_type, $this->selected_record_id);
            } else {
              $this->selected_record_id = null;
            }
        
          } elseif ($eager_search_data) {
            $this->selected_record_id = $this->_search($eager_search_data);
          } else {
            $this->selected_record_id = null;
          }
        }
    
      }
  
    }
    
    if ($this->session->get('portal') == 'teacher') {
      
      $focus = $this->session->get('focus');
      
      if (isset($focus[$record_type])) {
        $this->selected_record_id = $focus[$record_type];
      }
    
      $selected_record_type = $record_type;
    }
    
    $this->_setSelectedRecord($this->selected_record_id);
    
  }
  
  public function getSubmitMode() {
    if ($this->getRecordType() != '' AND $this->getSelectedRecord() == '' AND !$this->getAddMode()) {
      return 'search';
    } else {
      return 'edit';
    }
  }
  
  public function getSelectedRecordBarTemplate() {
    return $this->delegate->getSelectedRecordBarTemplate();
  }
  
  public function getRecordBarTemplate() {
    return $this->delegate->getRecordBarTemplate();
  }
  
  public function getDelegatesPath() {
    return substr(get_class($this->delegate), 0, strripos(get_class($this->delegate), '\\'));
  }
  
  private function _setSelectedRecord($record_id) {
    $this->selected_record = $this->delegate->get($record_id);
    $this->selected_record_id = $this->selected_record[$this->delegate->getBaseKeyFieldName()];
    return $this->selected_record_id;
  }
  
  public function getRecordType() {
    return $this->record_type;
  }
  
  public function getSelectedRecord() {
    return $this->selected_record;
  }
  public function getSelectedRecordID() {
    return $this->selected_record_id;
  }
  
  public function setAddMode($add_mode = null) {
    if ($add_mode == 'Y')
      $this->add_mode = true;
  }
  
  public function getAddMode() {
    return $this->add_mode;
  }
  
  private function _getPreviousRecordID() {
    $id_stack = $this->delegate->getRecordIDStack();
    
    if ($this->selected_record_id) {
      for ($i = 0; $i < count($id_stack); $i++) {      
        if ($id_stack[$i]['ID'] == $this->selected_record_id) {
          $current_key = $i;
          break;
        }
      }
    
      if (isset($current_key) && ($current_key - 1) >= 0 && ($current_key - 1) < count($id_stack))
        $new_key = $current_key - 1;
      else {
        end($id_stack);
        $new_key = key($id_stack);
      }
    } else {
      end($id_stack);
      $new_key = key($id_stack);
    }
    
    if (isset($id_stack[$new_key]['ID'])) {
      return $id_stack[$new_key]['ID'];
    } else {
      $this->flash->add('info', 'No records found.');
    }
  }
  
  private function _getNextRecordID() {
    $id_stack = $this->delegate->getRecordIDStack();

    if ($this->selected_record_id) {
      for ($i = 0; $i < count($id_stack); $i++) {      
        if ($id_stack[$i]['ID'] == $this->selected_record_id) {
          $current_key = $i;
          break;
        }
      }
    
      if (isset($current_key) && ($current_key + 1) < count($id_stack))
        $new_key = $current_key + 1;
      else
        $new_key = 0;
    } else {
      reset($id_stack);
      $new_key = key($id_stack);
    }
    
    if (isset($id_stack[$new_key]['ID'])) {
      return $id_stack[$new_key]['ID'];
    } else {
      $this->flash->add('info', 'No records found.');
    }
  }
  
  private function _search($post_data) {
    
    // get base table
    $base_table = $this->delegate->getBaseTable();
    $base_field = $this->delegate->getBaseKeyFieldName();

    $select_obj = \Kula\Component\Database\DB::connect('read')->select($base_table);
    $select_obj->add_field($base_table, $base_field);
    // Get fields from base_table in array
    if (isset($post_data[$base_table])) {
      $select_obj = $select_obj->fields($base_table, array_keys($post_data[$base_table]));
    // Create conditions
    foreach($post_data[$base_table] as $key => $value) {
      // check for permission
      if (\Kula\Component\Permission\Permission::getPermissionForSchemaObject($base_table, $key, \Kula\Component\Permission\Permission::READ)) {
        if (is_array($value)) {  
          $select_obj = $select_obj->condition($key, $value, 'IN', $base_table);  
        } elseif (is_int($value)) { 
          $select_obj = $select_obj->condition($key, $value, '=', $base_table);  
        } else {
          $select_obj = $select_obj->condition($key, $value.'%', 'LIKE', $base_table);  
        }
      } else {
        $this->flash->add('error', 'Searching in ' . $base_table . '.' . $key . ' with no permission.');
      }
    }
    unset($post_data[$base_table]);
    }
    
    // Modify table first
    $select_obj = $this->delegate->modifySearchDBOBject($select_obj);
    
    // Get any other tables
    if (count($post_data) > 0) {
      $i = 0;
      // loop through each table
      foreach($post_data as $table => $table_data) {
        
        foreach($table_data as $field => $value) {
          // check for permission
          if (\Kula\Component\Permission\Permission::getPermissionForSchemaObject($table, $field, \Kula\Component\Permission\Permission::READ)) {
            if (is_array($value)) {  
              $select_obj = $select_obj->condition($table.'.'.$field, $value);
            } elseif (is_int($value)) { 
              $select_obj = $select_obj->condition($table.'.'.$field, $value);
            } else {
              $select_obj = $select_obj->condition($table.'.'.$field, $value . '%', 'LIKE');
            }
          } else {
            $this->flash->add('error', 'Searching in ' . $table . '.' . $field . ' with no permission.');
          }
        }
        
        // get link to base table
        
        
        //$select_obj = $select_obj->left_join($table, $table.'_'.$i, array_keys($post_data[$table]), $condition);
        $i++;
      }
      
    }
    
    
    $select_obj = $select_obj->range(0, 1);
    //echo $select_obj->sql();
    $result = $select_obj->execute()->fetch();
    
    if (isset($result[$base_field])) {
      return $result[$base_field];
    } else {
      $this->flash->add('info', 'No matches found.');
      return '';
    }
    
  }
  
}