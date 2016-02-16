<?php

namespace Kula\Core\Component\Focus;

class Term {
  
  public function __construct($cache) {
    $this->cache = $cache;
  }
  
  public function getAllTermIDs() {
    if ($this->cache->exists('term.all')) {
      return $this->cache->get('term.all');
    }
  }
  
  public function getTermName($termID) {
    if ($this->cache->exists('term.'.$termID)) {
      return $this->cache->get('term.'.$termID)['TERM_NAME'];
    }
  }
  
  public function getTermAbbreviation($termID) {
    if ($this->cache->exists('term.'.$termID)) {
      return $this->cache->get('term.'.$termID)['TERM_ABBREVIATION'];
    }
  }
  
  public function getStartDate($termID) {
    if ($this->cache->exists('term.'.$termID)) {
      return $this->cache->get('term.'.$termID)['START_DATE'];
    }
  }
  
  public function getCurrentTermID() {
    $today = date('Y-m-d');
    
    $last_start_date_id = null;
    
    foreach ($this->cache->get('term.all') as $id) {
      $term = $this->cache->get('term.'.$id);
      if ($term['START_DATE'] > $today) {
        return $last_start_date_id;
      }
      $last_start_date_id = $id;
    }
  }

}