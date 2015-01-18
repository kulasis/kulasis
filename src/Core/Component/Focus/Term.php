<?php

namespace Kula\Core\Component\Focus;

class Term {
  
  private static $all_terms;
  
  public static function getAllTerms() {
    
    if (count(self::$all_terms) == 0) {
    
      // Get all organizations in an array with [organization_id] = organization_array
      $term_results = \Kula\Component\Database\DB::connect('read')->select('CORE_TERM', 'term')
        ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION'))
        ->order_by('START_DATE')
        ->execute();
      while ($term_row = $term_results->fetch()) {
        self::$all_terms[$term_row['TERM_ID']] = $term_row;
      }
      
    }
      
    return self::$all_terms;
  }
  
  public static function getAllTermIDs() {
    return array_keys(self::getAllTerms());
  }
  
}