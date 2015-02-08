<?php

namespace Kula\Core\Component\Focus;

class Term {
  
  private $terms;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function awake($db) {
    $this->db = $db;
  }
  
  public function loadTerms() {
    
    // Get all organizations in an array with [organization_id] = organization_array
    $term_results = $this->db->db_select('CORE_TERM', 'term', array('target' => 'schema'))
      ->fields('term', array('TERM_ID', 'TERM_NAME', 'TERM_ABBREVIATION', 'START_DATE'))
      ->orderBy('START_DATE')
      ->execute();
    while ($term_row = $term_results->fetch()) {
      $this->terms[$term_row['TERM_ID']] = $term_row;
    }
  
  }
  
  public function getAllTermIDs() {
    return array_keys($this->terms);
  }
  
  public function getTermName($termID) {
    return $this->terms[$termID]['TERM_NAME'];
  }
  
  public function getTermAbbreviation($termID) {
    return $this->terms[$termID]['TERM_ABBREVIATION'];
  }
  
  public function getStartDate($termID) {
    return $this->terms[$termID]['START_DATE'];
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('terms');
  }
  
}