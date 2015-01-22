<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Schema\Table;

class Field {
  
  private $name;
  private $db_ID;
  private $db_Name;
  private $db_Type;
  private $db_Length;
  private $db_Precision;
  private $db_Null;
  private $db_Default;
  private $db_Primary;
  private $db_ParentField;
  private $field_Name;
  private $field_Type;
  private $field_Size;
  private $field_ColumnLength;
  private $field_RowHeight;
  private $field_Class;
  private $lookup;
  private $chooser;
  private $columnName;
  private $labelName;
  private $labelPosition;
  private $updateField;
  
  private $table;

  public function __construct(Table $table, $name, $db_ID, $db_Name, $db_Type, $db_Length, $db_Precision, $db_Null, $db_Default, $db_Primary, $db_ParentField, $field_Name, $field_Type, $field_Size, $field_ColumnLength, $field_RowHeight, $field_Class, $lookup, $chooser, $columnName, $labelName, $labelPosition, $updateField) {
    
    $this->table = $table;
    
    $this->name = $name;
    $this->db_ID = $db_ID;
    $this->db_Name = $db_Name;
    $this->db_Type = $db_Type;
    $this->db_Length = $db_Length;
    $this->db_Precision = $db_Precision;
    $this->db_Null = $db_Null;
    $this->db_Default = $db_Default;
    $this->db_Primary = $db_Primary;
    $this->db_ParentField = $db_ParentField;
    $this->field_Name = $field_Name;
    $this->field_Type = $field_Type;
    $this->field_Size = $field_Size;
    $this->field_ColumnLength = $field_ColumnLength;
    $this->field_RowHeight = $field_RowHeight;
    $this->field_Class = $field_Class;
    $this->lookup = $lookup;
    $this->chooser = $chooser;
    $this->columnName = $columnName;
    $this->labelName = $labelName;
    $this->labelPosition = $labelPosition;
    $this->updateField = $updateField;
    
  }
  
  public function getTable() {
    return $this->table;
  }
  
  public function getFieldType() {
    return $this->field_Type;
  }
  
  public function getFieldSize() {
    return $this->field_Size;
  }
  
  public function getClass() {
    return $this->field_Class;
  }
  
  public function getChooser() {
    return $this->chooser;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getDBName() {
    return $this->db_Name;
  }
  
  public function getLabelName() {
    return $this->labelName;
  }
  
  public function isPrimary() {
    if ($this->db_Primary)
      return true;
  }
  
}