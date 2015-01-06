<?php

namespace Kula\Core\Component\Schema;

class Field {
  
  private $name;
  private $description;
  private $primary;
  private $parent;
  private $db_columnName;
  private $db_columnType;
  private $db_columnSize;
  private $db_columnLength;
  private $db_columnPrecision;
  private $db_columnNull;
  private $class;
  private $field_type;
  private $field_size;
  private $field_cols;
  private $field_rows;
  private $chooserClass;
  private $lookup;
  private $columnName;
  private $labelName;
  private $labelPosition;
  private $updateField;
  private $log = array();
  
  public function __construct($name, $description = null) {
    $this->name = $name;
    $this->description = $description;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getDescription() {
    return $this->description;
  }
  
  public function getPrimary() {
    return $this->primary;
  }
  
  public function setPrimary($primary) {
    $this->primary = $primary;
  }
  
  public function setParent($parent) {
    $this->parent = $parent;
  }
  
  public function getParent() {
    return $this->parent;
  }
  
  public function getDBColumnName() {
    return $this->db_columnName;
  }
  
  public function getDBColumnLength() {
    return $this->db_columnLength;
  }
  
  public function getDBColumnType() {
    return $this->db_columnType;
  }
  
  public function getDBColumnSize() {
    return $this->db_columnSize;
  }
  
  public function getDBColumnNull() {
    return $this->db_columnNull;
  }
  
  public function setColumnName($columnName) {
    $this->columnName = $columnName;
  }
  
  public function setDBColumnName($columnName) {
    $this->db_columnName = $columnName;
  }
  
  public function setDBColumnType($columnType) {
    $this->db_columnType = $columnType;
  }
  
  public function setDBColumnSize($columnSize) {
    $this->db_columnSize = $columnSize;
  }
  
  public function setDBColumnNull($columnNull) {
    $this->db_columnNull = $columnNull;
  }
  
  public function setDBColumnLength($columnLength) {
    $this->db_columnLength = $columnLength;
  }
  
  public function setDBColumnPrecision($columnPrecision) {
    $this->db_columnPrecision = $columnPrecision;
  }
  
  public function setClass($class) {
    $this->class = $class;
  }
  
  public function setFieldType($field_type) {
    $this->field_type = $field_type;
  }
  
  public function setFieldSize($field_size) {
    $this->field_size = $field_size;
  }
  
  public function setFieldCols($field_cols) {
    $this->field_cols = $field_cols;
  }
  
  public function setFieldRows($field_rows) {
    $this->field_rows = $field_rows;
  }
  
  public function setChooserClass($chooserClass) {
    $this->chooserClass = $chooserClass;
  }
  
  public function setLookup($lookup) {
    $this->lookup = $lookup;
  }
  
  public function setLabelName($labelName) {
    $this->labelName = $labelName;
  }
  
  public function setLabelPosition($labelPosition) {
    $this->labelPosition = $labelPosition;
  }
  
  public function setUpdateField($updateField) {
    $this->updateField = $updateField;
  }
  
  public function log($bundlePath, $action) {
    $this->log[] = array('bundle_path' => $bundlePath, 'action' => $action);
  }
}