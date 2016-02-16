<?php

namespace Kula\Core\Component\Focus;
  
class TermLoader {

  private $terms;
  
  private $termIDs;
  
  public function __construct($db, $cache) {
    $this->db = $db;
    $this->cache = $cache;
  }
  
  public function loadTerms() {
    
    // Get all organizations in an array with [organization_id] = organization_array
    $term_results = $this->db->db_select('CORE_TERM', 'term', array('target' => 'schema'))
      ->fields('term', array('TERM_ID', 'TERM_NAME', 'TERM_ABBREVIATION', 'START_DATE'))
      ->orderBy('START_DATE')
      ->execute();
    while ($term_row = $term_results->fetch()) {
      $this->terms[$term_row['TERM_ID']] = $term_row;
      $this->termIDs[] = $term_row['TERM_ID'];
      
      $this->cache->add('term.'.$term_row['TERM_ID'], $term_row);
    }
    
    $this->cache->add('term.all', $this->termIDs);
    
    return array('termIDs' => $this->termIDs, 'terms' => $this->terms);
  
  }

}