<?php

namespace Kula\Core\Component\Focus;

class Organization {
  
  private $organizations = array();
  private $schools = array();
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function awake($db) {
    $this->db = $db;
  }
  
  public function loadOrganization() {
    // Get all organizations in an array with [organization_id] = organization_array
    $organization_results = $this->db->db_select('CORE_ORGANIZATION', 'org')
      ->fields('org', array('ORGANIZATION_ID', 'PARENT_ORGANIZATION_ID', 'ORGANIZATION_NAME', 'ORGANIZATION_ABBREVIATION', 'ORGANIZATION_TYPE'))
      ->orderBy('PARENT_ORGANIZATION_ID')
      ->orderBy('ORGANIZATION_NAME')
      ->execute();
    while ($organization_row = $organization_results->fetch()) {
      
      if (isset($this->organizations[$organization_row['PARENT_ORGANIZATION_ID']])) {
        $parent = $this->organizations[$organization_row['PARENT_ORGANIZATION_ID']];
      } else {
        $parent = null;
      }
      
      $organization = new OrganizationUnit($organization_row['ORGANIZATION_ID'], $organization_row['ORGANIZATION_NAME'], $organization_row['ORGANIZATION_ABBREVIATION'], $organization_row['ORGANIZATION_TYPE'], $parent);
      
      $this->organizations[$organization_row['ORGANIZATION_ID']] = $organization;
      
      if ($parent) {
        $parent->addChild($organization);
      }
      
      if ($organization_row['ORGANIZATION_TYPE'] == 'S') {
        $this->schools[$organization_row['ORGANIZATION_ID']] = $organization;
        
        // Get terms
        $orgTerms = $this->db->db_select('CORE_ORGANIZATION_TERMS', 'orgterms')
          ->fields('orgterms')
          ->condition('orgterms.ORGANIZATION_ID', $organization_row['ORGANIZATION_ID'])
          ->execute();
        while ($orgTermRow = $orgTerms->fetch()) {
          $organization->addTerm($orgTermRow['ORGANIZATION_TERM_ID'], $orgTermRow['TERM_ID']);
        }
      }
      
      unset($organization, $parent);
    }
  }
  
  public function getOrganization($id) {
    return $this->organizations[$id];
  }
  
  public function getTermsForOrganization($id, $nested = false) {
    if ($nested === false) {
      $this->terms = array();
    }
    $organization = $this->organizations[$id];
    if ($organization->getChildren()) {
      foreach($organization->getChildren() as $child) {
         $this->getTermsForOrganization($child->getID(), true);
      }
    } elseif ($nested === true) {
      if ($organization->getTermIDs()) {
        foreach($organization->getTermIDs() as $term) {
          $this->terms[$term] = $term;
        }
      }
      return;
    }
    return $this->terms;
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('organizations', 'schools');
  }
  
}