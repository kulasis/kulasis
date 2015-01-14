<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\DB\DB as DB;
use Kula\Core\Component\Schema\Schema as Schema;
use Symfony\Component\HttpFoundation\RequestStack as RequestStack;
use Kula\Core\Component\DB\PosterRecord as PosterRecord;

class Poster {
  
  private $db;
  private $requestStack;
  private $schema;
  
  private $records = array();
  
  private $result;
  private $primary_keys;
  
  private $original_data;
  
  private $hasViolations = false;
  private $isPosted = false;
  

  public function __construct(DB $db, RequestStack $requestStack, Schema $schema) {
    $this->db = $database;
    $this->requestStack = $requestStack;
    $this->schema = $schema;
    
    $this->result = null;
    $this->hasViolations = false;
    $this->isPosted = false;
  }
  
  public function add($table, $id, $fields) {
    $this->records[] = new PosterRecord($this->db, $this->schema, PosterRecord::ADD, $table, $id, $fields);
  }
  
  public function addMultiple(array $post) {
    ksort($post);
    foreach($post as $table => $tableRow) {
      foreach($tableRow as $id => $row) {
        $this->add($table, $id, $row);
      }
    }
  }
  
  public function edit($table, $id, $fields) {
    $this->records[] = new PosterRecord($this->db, $this->schema, PosterRecord::EDIT, $table, $id, $fields);
  }
  
  public function editMultiple(array $post) {
    ksort($post);
    foreach($post as $table => $tableRow) {
      foreach($tableRow as $id => $row) {
        $this->edit($table, $id, $row);
      }
    }
  }
  
  public function delete($table, $id, $fields) {
    $this->records[] = new PosterRecord($this->db, $this->schema, PosterRecord::DELETE, $table, $id, $fields);
  }
  
  public function deleteMultiple(array $post) {
    ksort($post);
    foreach($post as $table => $tableRow) {
      foreach($tableRow as $id => $row) {
        $this->delete($table, $id, $row);
      }
    }
  }
  
  public function process() {
    foreach($this->records as $record) {
      $record->process();
    }
  }
  /*
  public function process($add = null, $edit = null, $delete = null, $db = null) {
    
    
    // prepare different variables
    $this->add = self::prepare($this->add);
    
    $this->edit = self::prepare($this->edit);
    
    // for edit, compare against existing values and remove same
    self::prepareForEdit();
    
    // insert rows to be inserted
    self::insertRows();
    
    // update rows
    self::updateRows();
    
    // delete rows
    self::deleteRows();
    
    // perform transactions  
    if (!$this->has_violations) {
      if (count($this->result) > 0) {
        self::doTransactions();
        $this->flash_bag->add('success', 'Changes saved.');
      } else {
        $this->flash_bag->add('info', 'No changes to perform.');  
      }
    }
    else {
      $violations = array();
      $violation_fields = array();
      
      foreach($this->result as $tran_type => $table_row) {
        foreach($table_row as $table => $row) {
          foreach($row as $id => $element) {
            foreach($element['violations'] as $violation) {
              $attribute = trim($violation->getPropertyPath(), '[]');
              if (isset($schema[$table][$attribute]['DISPLAY_NAME']))
                $violation_message = $schema[$table][$attribute]['DISPLAY_NAME'].': '.$violation->getMessage();
              else
                $violation_message = 'NO PERMISSION: '.$violation->getMessage();
              $violations[$violation_message] = $violation_message;
              
              if ($tran_type == 'insert') $post_mode = 'add';
              if ($tran_type == 'update') $post_mode = 'edit';
              if ($tran_type == 'delete') $post_mode = 'delete';
              
              $violation_fields[] = $post_mode . '['.$table.']' . '['.$id.']' . '['.$attribute.']';
            }
          }
        }
      }
      
      $violation_string = '';
      foreach($violations as $key => $value) {
        $violation_string .= '<li>' . $value . '</li>';
      }
      if ($this->requestStack->getCurrentRequest()->isXmlHttpRequest()) {
        throw new PosterFormException('Changes not saved.<ul>' . $violation_string . '</ul>', $violation_fields);
      } else {
        $this->flash_bag->add('error', 'Changes not saved.<ul>' . $violation_string . '</ul>');
      }
    }
  }
  
  public function hasViolations() {
    return $this->has_violations;
  }
  
  public function getPostedValue($name) {
    
    $action_type = substr($name, 0, strpos($name, '['));
    
    $array = substr($name, strpos($name, '['), strlen($name));
    
    $saved = $this->{$action_type};
    
    $result = stringToArray($array, $saved, true);
    
    return $result;
  }
  
  
  public function prepareForEdit() {
    
    $schema = \Kula\Component\Schema\Schema::getSchemaObject();
    
    // for each table in array
    if ($this->edit) {
    foreach($this->edit as $table => $table_row) {
      
      ksort($this->edit[$table]);
      
      $db_data = array();
      
      $primary_key = \Kula\Component\Schema\Schema::getPrimaryKeyFieldForTable($table);
      $checkbox_fields = \Kula\Component\Schema\Schema::getCheckboxFieldsForTable($table);
      $date_fields = \Kula\Component\Schema\Schema::getDateFieldsForTable($table);
      $time_fields = \Kula\Component\Schema\Schema::getTimeFieldsForTable($table);
      
      if (!$primary_key) {
        throw new \Exception('Missing primary key for table ' . $table . ' in CORE_SCHEMA_FIELDS.');
      }
      
      if ($table_row) {
      
      // Get records from table
      $db_table_result = $this->db->select($table)
        ->predicate($primary_key, array_keys($table_row), 'IN')
        ->execute();
      while ($db_table_row = $db_table_result->fetch()) {
        $db_data[$db_table_row[$primary_key]] = $db_table_row;
      }
      
      $this->original_data['edit'][$table] = $db_data;
      
      }
      
      if ($table_row) {
      // for each row in table
      foreach($table_row as $row_id => $row) {
        // for each element in row
        if ($row) {
        foreach ($row as $key => $value) {
          if (isset($this->edit[$table][$row_id][$key]['value'])) {
            $this->edit[$table][$row_id][$key] = $this->edit[$table][$row_id][$key]['value'];            
          }
          if (isset($this->edit[$table][$row_id][$key]['chooser'])) {
            unset($this->edit[$table][$row_id][$key]['chooser']);
          }
          if (in_array($key, $date_fields)) {
            if (isset($this->edit[$table][$row_id][$key]) AND $this->edit[$table][$row_id][$key] != '') {
              
              // Check if slashes or dashes in place
              $value = $this->edit[$table][$row_id][$key];
              if (strpos($value, '/') === false AND strpos($value, '-') === false) {
                // split string and use mktime to determine date
                $new_date = mktime(0, 0, 0, substr($value, 0, 2), substr($value, 2, 2), substr($value, 4, strlen($value)-4));
              } else {
                $new_date = strtotime($value);
              }
              $this->edit[$table][$row_id][$key] = date('Y-m-d', $new_date);
            }
          }
          if (in_array($key, $time_fields)) {
            if (isset($this->edit[$table][$row_id][$key]) AND $this->edit[$table][$row_id][$key] != '') {
              $this->edit[$table][$row_id][$key] = date('H:i:s', strtotime($this->edit[$table][$row_id][$key]));
            }
          }
          // turn all blank values to null
          if (!is_array($this->edit[$table][$row_id][$key]) && trim($this->edit[$table][$row_id][$key]) == '') {
            $this->edit[$table][$row_id][$key] = null;
          }
          
          if (strpos($key, 'CALC_') !== false || (array_key_exists($key, $db_data[$row_id]) && 
              !in_array($key, $checkbox_fields) && 
              $db_data[$row_id][$key] == $this->edit[$table][$row_id][$key])) {
            unset($this->edit[$table][$row_id][$key]);
          }
        
        }
        // check for unchecked checkboxes
        if ($checkbox_fields) {
          foreach($checkbox_fields as $checkbox_field) {
            
            if (array_key_exists($checkbox_field, $this->edit[$table][$row_id]) AND 
                isset($this->edit[$table][$row_id][$checkbox_field]['checkbox_hidden']) AND
                $db_data[$row_id][$checkbox_field] != 'Y' AND 
                isset($this->edit[$table][$row_id][$checkbox_field]['checkbox']) AND
                $this->edit[$table][$row_id][$checkbox_field]['checkbox'] == 'Y') {
              $this->edit[$table][$row_id][$checkbox_field] = 'Y';
            } elseif (array_key_exists($checkbox_field, $this->edit[$table][$row_id]) AND 
                isset($this->edit[$table][$row_id][$checkbox_field]['checkbox_hidden']) AND
                $db_data[$row_id][$checkbox_field] == 'Y' AND 
                !isset($this->edit[$table][$row_id][$checkbox_field]['checkbox'])) {
              $this->edit[$table][$row_id][$checkbox_field] = 'N';
            } else {
              unset($this->edit[$table][$row_id][$checkbox_field]);
            }
          }
        }
        
        }
        // no more elements so remove
        if (count($this->edit[$table][$row_id]) == 0)  
          unset($this->edit[$table][$row_id]);
        else {
          // row contains data so validate
          $permission_violations = array();
          // Check for permission
          foreach($this->edit[$table][$row_id] as $attribute => $value) {
            if (!\Kula\Component\Permission\Permission::getPermissionForSchemaObject($table, $attribute, \Kula\Component\Permission\Permission::WRITE)) {
              $permission_violations[] = new \Symfony\Component\Validator\ConstraintViolation('Attempted to update ' . $table . '.' . $attribute . ' with no write permission.', 'Attempted to update {field} with no permission.', array('field' => $table . '.' . $attribute), 'Array', $table . '.' . $attribute, $value);
              unset($this->edit[$table][$row_id][$attribute]);
            }
          }
          $permission_violations = new \Symfony\Component\Validator\ConstraintViolationList($permission_violations);
          
          // begin validation
          // merge new data with existing row
          $row_to_validate = array_merge($db_data[$row_id], $this->edit[$table][$row_id]);
          $schema = reset(\Kula\Component\Schema\Schema::getSchemaObject()[$table]);
          if (isset($schema['VALIDATOR'])) {
            $validator_obj = new \Kula\Component\Validator\Validator($schema['VALIDATOR'], $row_to_validate);
            $violations = $validator_obj->getViolations();
            $violations->addAll($permission_violations);
            if (count($violations) > 0) {
              $this->has_violations = true;
              $this->result['update'][$table][$row_id]['violations'] = $violations;
            }
          } 
        } // end counting new elements
      }
      }
    }
    }
  }
  
  public function insertRows() {

    // look through tables
    if ($this->add) {
    foreach($this->add as $table => $table_row) {
      
      $checkbox_fields = \Kula\Component\Schema\Schema::getCheckboxFieldsForTable($table);
      $date_fields = \Kula\Component\Schema\Schema::getDateFieldsForTable($table);
      $time_fields = \Kula\Component\Schema\Schema::getTimeFieldsForTable($table);
      
      // for each row in new area
      foreach($table_row as $row_id => $row) {
          
          
          // check for unchecked checkboxes
          if ($checkbox_fields) {
            foreach($checkbox_fields as $checkbox_field) {
          
              if (array_key_exists($checkbox_field, $row) AND 
                  isset($row[$checkbox_field]['checkbox_hidden']) AND
                  isset($row[$checkbox_field]['checkbox']) AND
                  $row[$checkbox_field]['checkbox'] == 'Y') {
                  $row[$checkbox_field] = 'Y';
              } elseif (array_key_exists($checkbox_field, $row) AND 
                  isset($row[$checkbox_field]['checkbox_hidden']) AND
                  $row[$checkbox_field] == 'Y' AND 
                  !isset($row[$checkbox_field]['checkbox'])) {
                $row[$checkbox_field] = 'N';
              } else {
                unset($row[$checkbox_field]);
              }
            }
          }
          
          foreach($row as $key => $value) {
            
            if (isset($row[$key]['value'])) {
              $row[$key] = $row[$key]['value'];            
            }
            if (isset($row[$key]['chooser'])) {
              unset($row[$key]['chooser']);
            }
            if (in_array($key, $date_fields)) {
              if ($row[$key] != '') {
                // Check if slashes or dashes in place
                $value = $row[$key];
                if (strpos($value, '/') === false AND strpos($value, '-') === false) {
                  // split string and use mktime to determine date
                  $new_date = mktime(0, 0, 0, substr($value, 0, 2), substr($value, 2, 2), substr($value, 4, strlen($value)-4));
                } else {
                  $new_date = strtotime($value);
                }
                
                $row[$key] = date('Y-m-d', $new_date);
              }
            }
            if (in_array($key, $time_fields)) {
              if ($row[$key] != '') {
                $row[$key] = date('H:i:s', strtotime($row[$key]));
              }
            }
            
            if (!is_array($row[$key]) AND trim($row[$key]) == '') {
              unset($row[$key]);
            }
            
            
          }
          
          

          if (count($row) > 0) {
            // remove hidden elements
            if (isset($row['hidden'])) { 
            $hidden_elements = $row['hidden'];
            $row = array_merge($row, $hidden_elements);    
            unset($row['hidden']);
            }
            
            $permission_violations = array();
            // Check for permission to insert into table
            if (!\Kula\Component\Permission\Permission::getPermissionForSchemaObject($table, null, \Kula\Component\Permission\Permission::ADD)) {
              $permission_violations[] = new \Symfony\Component\Validator\ConstraintViolation('Attempted to insert into ' . $table  . ' with no permission to insert into table.', 'Attempted to insert into {table} with no permission to insert into table.', array('table' => $table), 'Array', $table, $value);
            } else {
            // Check for permission for fields
            foreach($this->add[$table][$row_id] as $attribute => $value) {
              if (!\Kula\Component\Permission\Permission::getPermissionForSchemaObject($table, $attribute, \Kula\Component\Permission\Permission::WRITE)) {
                $permission_violations[] = new \Symfony\Component\Validator\ConstraintViolation('Attempted to insert into ' . $table . '.' . $attribute . ' with no write permission.', 'Attempted to insert into {field} with no permission.', array('field' => $table . '.' . $attribute), 'Array', $table . '.' . $attribute, $value);
                unset($this->add[$table][$row_id][$attribute]);
              }
            }
            }
            $permission_violations = new \Symfony\Component\Validator\ConstraintViolationList($permission_violations);
            
            // Go for validation
            $schema = reset(\Kula\Component\Schema\Schema::getSchemaObject()[$table]);
            if (isset($schema['VALIDATOR'])) {
              $validator_obj = new \Kula\Component\Validator\Validator($schema['VALIDATOR'], $row);
              $violations = $validator_obj->getViolations();
              $violations->addAll($permission_violations);
              if (count($violations) > 0) {
                $this->has_violations = true;
                $this->result['insert'][$table][$row_id]['violations'] = $violations;
              }
            } else {
              if (count($permission_violations) > 0) {
                $this->has_violations = true;
                $this->result['insert'][$table][$row_id]['violations'] = $permission_violations;
              }
            }
            
            // Create insert row if no validation errors
            if (!isset($this->result['insert'][$table][$row_id]['violations'])) {
              
              if ($schema['TIMESTAMPS'] == 'Y') {
                $row['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
                $row['CREATED_USERSTAMP'] = $this->session->get('user_id');
              }
              $this->result['insert'][$table][$row_id]['db_obj'] = $this->db->insert($table)->fields($row);
              
              $primary_key = \Kula\Component\Schema\Schema::getPrimaryKeyFieldForTable($table);
              if (isset($row[$primary_key]))
                $this->primary_keys[$table][$row_id] = $row[$primary_key];
            }
        }
        }
      
      
      //unset($this->add[$table]['new']);
        
    }
    }
    
  }
  
  public function updateRows() {

    // look through tables
    if ($this->edit) {
      
    foreach($this->edit as $table => $table_row) {
        
      $primary_key = \Kula\Component\Schema\Schema::getPrimaryKeyFieldForTable($table);
      $schema = reset(\Kula\Component\Schema\Schema::getSchemaObject()[$table]);
      
      // for each row in table
      foreach($table_row as $row_id => $row) {
        
        if (isset($row['hidden']))
          unset($row['hidden']);
        
        if (is_array($row) AND count($row) > 0 AND !isset($this->result['update'][$table][$row_id]['violations'])) {
        
        if ($schema['TIMESTAMPS'] == 'Y') {
            $row['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
            $row['UPDATED_USERSTAMP'] = $this->session->get('user_id');
          }
          
        $this->result['update'][$table][$row_id]['db_obj'] = $this->db->update($table)
          ->fields($row)
          ->predicate($primary_key, $row_id);
        }
      }
      
    }
    }
    
  }
  
  public function deleteRows() {
    
    // for each table in array
    if ($this->delete) {
    foreach($this->delete as $table => $table_row) {
      
      $primary_key = \Kula\Component\Schema\Schema::getPrimaryKeyFieldForTable($table);
      
      if (!$primary_key) {
        throw new \Exception('Missing primary key for table ' . $table . ' in CORE_SCHEMA_FIELDS.');
      }
      
      // Get records from table
      $db_table_result = $this->db->select($table)
        ->predicate($primary_key, array_keys($table_row), 'IN')
        ->execute();
      while ($db_table_row = $db_table_result->fetch()) {
        $this->original_data['delete'][$table][$db_table_row[$primary_key]] = $db_table_row;
      }
      
      // for each row in table
      foreach($table_row as $row_id => $row) {

        // if delete_row set, then delete row
        if (isset($row['delete_row']) && $row['delete_row'] == 'Y') {
          $permission_violations = array();
          // Check for permission to delete from table
          if (!\Kula\Component\Permission\Permission::getPermissionForSchemaObject($table, null, \Kula\Component\Permission\Permission::DELETE)) {
            $permission_violations[] = new \Symfony\Component\Validator\ConstraintViolation('Attempted to delete from ' . $table  . ' with no permission to delete.', 'Attempted to delete from {table} with no permission to delete.', array('table' => $table . '.' . $attribute), 'Array', $table, $value);
          }
          $permission_violations = new \Symfony\Component\Validator\ConstraintViolationList($permission_violations);
          
          if (count($permission_violations) > 0) {
            $this->has_violations = true;
            $this->result['delete'][$table][$row_id]['violations'] = $permission_violations;
          }

          $primary_key = \Kula\Component\Schema\Schema::getPrimaryKeyFieldForTable($table);
          
          // Delete row if no validation errors
          if (!isset($this->result['delete'][$table][$row_id]['violations'])) {  
            // Delete row
            $this->result['delete'][$table][$row_id]['db_obj'] = $this->db->delete($table)
              ->predicate($primary_key, $row_id);
          }
        }
        
        if (isset($this->delete[$table][$row_id]['delete_row']))
        unset($this->delete[$table][$row_id]['delete_row']);
      }
        
    }
    }
    
  }
  
  public function doTransactions() {
    
    $started_trans = false;
    
    try {

    if (!$this->db->inTransaction()) {
      $started_trans = true;
      $this->db->beginTransaction();
    }
    
    // loop through delete, update and insert statements
    foreach($this->result as $trans_type => $trans_row) {
      // look through each table
      foreach($this->result[$trans_type] as $table => $table_row) {
        foreach($this->result[$trans_type][$table] as $row_id => $row_data) {
          
          
          //$sql = $connect->prepare('INSERT INTO LOG_SQL (STATEMENT, ARGUMENTS, `DATETIME`) VALUES (?, ?, NOW())');
          //$sql_statement = $this->result[$trans_type][$table][$row_id]['db_obj']->sql();
          //$sql_args = print_r($this->result[$trans_type][$table][$row_id]['db_obj']->arguments(), true);
          //$sql->execute(array($sql_statement, $sql_args));
          //echo $this->result[$trans_type][$table][$row_id]['db_obj']->sql();
          //var_dump($this->result[$trans_type][$table][$row_id]['db_obj']->arguments());
          if ($trans_type == 'insert' OR $trans_type == 'update')
            $fields_from_submitting = $this->result[$trans_type][$table][$row_id]['db_obj']->getFields();
          $this->result[$trans_type][$table][$row_id] = $this->result[$trans_type][$table][$row_id]['db_obj']->execute();
          
          if ($table != 'LOG_SESSION') {
          
          $session_obj =$this->session;
          $audit = $this->db->prepare('INSERT INTO LOG_AUDIT (USER_ID, SESSION_ID, CRUD_OPERATION, TABLE_NAME, RECORD_ID, OLD_RECORD, NEW_RECORD, CREATED_USERSTAMP, CREATED_TIMESTAMP)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
          if ($trans_type == 'insert') {
            $audit_row_id = $this->result[$trans_type][$table][$row_id];
            if (isset($this->primary_keys[$table][$row_id]) AND !$audit_row_id)
              $audit_row_id = $this->primary_keys[$table][$row_id];
            $audit_old_row = null;
            $audit_new_row = serialize($fields_from_submitting);
          } elseif ($trans_type == 'update') {
            $audit_row_id = $row_id;
            $audit_old_row = serialize($this->original_data['edit'][$table][$row_id]);
            $audit_new_row = serialize($fields_from_submitting);
          } elseif ($trans_type == 'delete') {
            $audit_row_id = $row_id;
            $audit_old_row = serialize($this->original_data['delete'][$table][$row_id]);
            $audit_new_row = null;
          }
          $audit->execute(array($session_obj->get('user_id'), $session_obj->get('session_id'), strtoupper(substr($trans_type, 0, 1)), $table, $audit_row_id, $audit_old_row, $audit_new_row, $session_obj->get('user_id'), date('Y-m-d H:i:s')));
          }
        }  
      }
    }
    
    if ($started_trans)
      $this->db->commit();
    
    } catch (\PDOException $e) {
        $this->db->rollback();
      throw new \PDOException($e);
    }
    
  }
  
  public function getResultForTable($action, $db_table) {
    if (isset($this->result[$action][$db_table]))
    return $this->result[$action][$db_table];
  }
  
  */
  
}