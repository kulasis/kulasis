<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\DB\DB as DB;
use Kula\Core\Component\Schema\Schema as Schema;
use Symfony\Component\HttpFoundation\RequestStack as RequestStack;

use Kula\Core\Component\Field\Field as Field;

use Kula\Core\Component\Permission\Permission;

use Symfony\Component\Validator\ConstraintViolationList;

class PosterRecord {
  
  protected $container;
  
  private $db;
  private $schema;
  
  private $crud;
  private $table;
  private $id;
  private $fields;
  
  private $originalRecord;
  
  private $violations;
  private $hasViolations;
  
  private $result;
  private $posted;
  private $noLog;
  
  const ADD = 'C';
  const EDIT = 'U';
  const DELETE = 'D';
  
  public function __construct($container, $crud, $table, $id, $fields = null) {
    $this->container = $container;
    $this->db = $this->container->get('kula.core.db');
    $this->schema = $this->container->get('kula.core.schema');
    $this->session = $this->container->get('kula.core.session');
    $this->permission = $this->container->get('kula.core.permission');
    $this->crud = $crud;
    $this->table = $table;
    $this->id = $id;
    $this->fields = $fields;
    $this->hasViolations = false;
    $this->posted = false;
    $this->noLog = false;
    $this->violations = new ConstraintViolationList();
  }
  
  public function process() {
    $this->getOriginalRecord();
    
    if ($this->crud == self::DELETE) {
      if ($this->fields['delete_row'] == 'Y') {
        unset($this->fields['delete_row']);
      }
      $this->verifyPermissions();
      if (!$this->hasViolations) {
        $this->result = $this->execute();
      }
    } else {
      $this->processConfirmation();
      $this->processSynthetic();
      $this->processCheckboxFields();
      $this->processDateFields();
      $this->processTimeFields();
      $this->processChoosers();
    
      $this->processBlankValues();
      if ($this->crud == self::EDIT) {
        $this->processSameValues();
      }
      if (count($this->fields) > 0) {
        $this->verifyPermissions();
        $this->validate();
    
        if (!$this->hasViolations) {
          $this->result = $this->execute();
          $this->posted = true;
        } else {
          
          $violation_string = '';
          foreach($this->violations as $violation) {
            $violation_string .= '<li>' . $violation->getPropertyPath() . ': '.$violation->getMessage().'</li>';
          }
          if ($this->container->get('request_stack')->getCurrentRequest()->isXmlHttpRequest()) {
            throw new PosterException('Changes not saved.<ul>' . $violation_string . '</ul>');
          } else {
            $this->container->get('session.flash_bag')->add('error', 'Changes not saved.<ul>' . $violation_string . '</ul>');
          }
        }
      }
    }
  }
  
  public function setNoLog($noLog) {
    $this->noLog = $noLog;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getField($field) {
    return $this->fields[$field];
  }
  
  public function getCRUD() {
    return $this->crud;
  }
  
  public function isPosted() {
    return $this->posted;
  }
  
  public function getResult() {
    return $this->result;
  }
  
  private function getOriginalRecord() {
    if ($this->crud == self::ADD)
      return false;
    $this->originalRecord = $this->db->db_select($this->schema->getTable($this->table)->getDBName(), 'originalTable')
      ->fields('originalTable')
      ->condition($this->schema->getDBPrimaryColumnForTable($this->table), $this->id)
      ->execute()->fetch();
  }
  
  private function processConfirmation() {
    foreach($this->fields as $fieldName => $field) {
      $confirmKey = $fieldName . '_confirmation';
      if (isset($this->fields[$confirmKey])) {
        if ($this->fields[$fieldName] != $this->fields[$confirmKey]) {
          throw new \Exception($key . ' and confirmation fields do not match.');
        }
        unset($this->fields[$confirmKey]);
      }
    }
  }
  
  private function processSynthetic() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getClass($fieldName)) {
      $class = '\\'.$this->schema->getClass($fieldName);
      if (method_exists($class, 'save')) {
        $syntheticField = new $class($this->container);
        $returnedValue = call_user_func_array(array($syntheticField, 'save'), array($field, $this->id));
        if ($returnedValue == Field::REMOVE_FIELD)
          unset($this->fields[$fieldName]);
        else
          $this->fields[$fieldName] = $returnedValue;
      }
      }
    }
  }
  
  private function processBlankValues() {
    foreach($this->fields as $fieldName => $field) {
      if (!is_array($field) AND trim($field) == '') {
        $this->fields[$fieldName] = null;
      }
    }
  }
  
  private function processSameValues() {
    foreach($this->fields as $fieldName => $field) {
      if (array_key_exists($this->schema->getField($fieldName)->getDBName(), $this->originalRecord) AND $this->originalRecord[$this->schema->getField($fieldName)->getDBName()] === $this->fields[$fieldName]) {
        unset($this->fields[$fieldName]);
      }
    }
  }
  
  private function processCheckboxFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'checkbox') {
        if (isset($field['checkbox_hidden']) OR isset($field['checkbox'])) {
          // Checkbox originally unchecked, now checked.
          if (($field['checkbox_hidden'] == '' OR $field['checkbox_hidden'] == '0') AND isset($field['checkbox']) AND $field['checkbox'] == '1') {
            $this->fields[$fieldName] = '1';
          } elseif (($field['checkbox_hidden'] == '1' AND !isset($field['checkbox']))) {
          // Checkbox originally checked, now unchecked.
            $this->fields[$fieldName] = '0';
          } else {
            unset($this->fields[$fieldName]);
          }
        } 
      }
    }
  }
  
  private function processDateFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'date') {
        if ($field != '') {
          // Check if slashes or dashes in place
          if (strpos($field, '/') === false AND strpos($field, '-') === false) {
            // split string and use mktime to determine date
            $newDate = mktime(0, 0, 0, substr($field, 0, 2), substr($field, 2, 2), substr($field, 4, strlen($field)-4));
          } else {
            $newDate = strtotime($field);
          }
          $this->fields[$fieldName] = date('Y-m-d', $newDate);
        }
      }
    }
  }
  
  private function processTimeFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'time') {
        if ($field != '') {
          $this->fields[$fieldName] = date('H:i:s', strtotime($field));
        }
      }
    }
  }
  
  private function processDateTimeFields() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'datetime') {
        if ($field != '') {
          $this->fields[$fieldName] = date('Y-m-d H:i:s', strtotime($field));
        }
      }
    }
  }
  
  private function processChoosers() {
    foreach($this->fields as $fieldName => $field) {
      if ($this->schema->getFieldType($fieldName) == 'chooser') {
        if (isset($field['value'])) {
          $this->fields[$fieldName] = $field['value'];
        } elseif (!is_array($field)) {
          $this->fields[$fieldName] = $field;
        }
      }
    }
  }
  
  private function validate() {
    // begin validation
    $schema = $this->schema->getTable($this->table);
    if ($validator = $schema->getDBClass()) {
      
      $fields = array();
      foreach($this->fields as $fieldName => $field) {
        $fields[$this->schema->getField($fieldName)->getDBName()] = $field;
      }
      
      // merge new data with existing row
      if ($this->crud == self::EDIT) {
        $row_to_validate = array_merge($this->originalRecord, $fields);
      } else {
        $row_to_validate = $fields;
      }
      //var_dump($row_to_validate);
      $validator_obj = new \Kula\Core\Bundle\FrameworkBundle\Service\Validator($validator, $row_to_validate);
      $violations = $validator_obj->getViolations();
      $this->violations->addAll($violations);
      if (count($violations) > 0) {
        $this->hasViolations = true;
      }
    }
  }
  
  private function verifyPermissions() {

    $permission_violations = array();
    if ($this->crud == self::ADD) {
      // Check for permission to insert into table
      if (!$this->permission->getPermissionForSchemaObject($this->table, null, Permission::ADD)) {
        $permission_violations[] = new \Symfony\Component\Validator\ConstraintViolation('Attempted to insert into ' . $this->table  . ' with no permission to insert into table.', 'Attempted to insert into {table} with no permission to insert into table.', array('table' => $this->table), 'Array', $this->table, null);
      } 
    }
    
    if ($this->crud == self::ADD || $this->crud == self::EDIT) {
      // Check for permission
      foreach($this->fields as $attribute => $value) {
        if (!$this->permission->getPermissionForSchemaObject($this->table, $attribute, Permission::WRITE)) {
          $permission_violations[] = new \Symfony\Component\Validator\ConstraintViolation('Attempted to {crud}' . $table . '.' . $attribute . ' with no write permission.', 'Attempted to {crud} {field} with no permission.', array('field' => $attribute, 'crud' => $this->crud), 'Array', $attribute, $value);
          unset($this->fields[$attribute]);
        }
      }
    }
    
    if ($this->crud == self::DELETE) {
      // Check for permission to delete from table
      if (!$this->permission->getPermissionForSchemaObject($this->table, null, Permission::DELETE)) {
        $permission_violations[] = new \Symfony\Component\Validator\ConstraintViolation('Attempted to delete from ' . $this->table  . ' with no permission to delete.', 'Attempted to delete from {table} with no permission to delete.', array('table' => $this->table), 'Array', $this->table, null);
      }
      
    }
    
    if (count($permission_violations) > 0) {
      $this->hasViolations = true;
      $this->violations = $this->violations->addAll($permission_violations);
    }
  }
  
  private function appendStamps() {
    if ($this->schema->getTable($this->table)->getDBTimestamps()) {
      $fields = array();
      if ($this->crud == self::ADD) {
        $fields['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
        $fields['CREATED_USERSTAMP'] = $this->session->get('user_id');
      }
      if ($this->crud == self::EDIT) {
        $fields['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
        $fields['UPDATED_USERSTAMP'] = $this->session->get('user_id');
      }
      return $fields;
    }
  }
  
  private function auditLog($fields = null) {
    
    $oldFields = $this->originalRecord;
    if (count($oldFields) > 0) {
      foreach($oldFields as $fieldName => $fieldValue) {
        if ($this->schema->getClassDB($this->schema->getTable($this->table)->getDBName(), $fieldName)) {
          $class = '\\'.$this->schema->getClassDB($this->schema->getTable($this->table)->getDBName(), $fieldName);
          if (method_exists($class, 'removeFromAuditLog')) {
            $syntheticField = new $class($this->container);
            if (call_user_func_array(array($syntheticField, 'removeFromAuditLog'), array()) === true) {
              unset($oldFields[$fieldName]);
            }
          }
        }
      }
    }
    
    $audit = $this->db->db_connection(array('target' => 'write'))->prepare('INSERT INTO '.$this->schema->getTable('Log.Audit.Changes')->getDBName().' (USER_ID, LOG_SESSION_ID, CRUD_OPERATION, TABLE_NAME, RECORD_ID, OLD_RECORD, NEW_RECORD, CREATED_USERSTAMP, CREATED_TIMESTAMP)
        VALUES (:user_id, :session_id, :crud, :table_name, :record_id, :old_record, :new_record, :created_userstamp, :created_timestamp)');
    $data = array(
        'user_id' => $this->session->get('user_id'), 
        'session_id' => $this->session->get('session_id'), 
        'crud' => $this->crud,
        'table_name' => $this->schema->getTable($this->table)->getDBName(), 
        'record_id' => $this->id, 
        'old_record' => (isset($this->originalRecord) AND count($oldFields) > 0) ? serialize($oldFields) : null, 
        'new_record' => count($fields) > 0 ? serialize($fields) : null, 
        'created_userstamp' => $this->session->get('user_id'), 
        'created_timestamp' => date('Y-m-d H:i:s'));
    $audit->execute($data);
  }
  
  private function execute() {
    if ($this->posted === false) {
      if ($this->crud == self::ADD OR $this->crud == self::EDIT) {
        $fields = array();
        foreach($this->fields as $fieldName => $field) {
          $fields[$this->schema->getField($fieldName)->getDBName()] = $field;
        }
        $fields += $this->appendStamps();
      }

      if ($this->crud == self::ADD) {
        $transaction = $this->db->db_transaction();
        $this->id = $this->db->db_insert($this->schema->getTable($this->table)->getDBName(), array('nolog' => $this->noLog))
          ->fields($fields)
          ->execute();
        $this->auditLog($fields);
        $transaction->commit();
        return $this->id;
      }
      if ($this->crud == self::EDIT) {
        $transaction = $this->db->db_transaction();
        $affectedRows = 0;

        $affectedRows = $this->db->db_update($this->schema->getTable($this->table)->getDBName(), array('nolog' => $this->noLog))
          ->fields($fields)
          ->condition($this->schema->getDBPrimaryColumnForTable($this->table), $this->id)
          ->execute();
        $this->auditLog($fields);

        $transaction->commit();
        return $affectedRows;
      }
      if ($this->crud == self::DELETE) {
        
        $transaction = $this->db->db_transaction();
        $affectedRows = 0;

        $affectedRows = $this->db->db_delete($this->schema->getTable($this->table)->getDBName(), array('nolog' => $this->noLog))
          ->condition($this->schema->getDBPrimaryColumnForTable($this->table), $this->id)->execute();
        $this->auditLog();

        $transaction->commit();
        return $affectedRows;
      }
    }
  }
}