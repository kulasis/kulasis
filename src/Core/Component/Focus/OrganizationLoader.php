<?php

namespace Kula\Core\Component\Focus;

class OrganizationLoader {

  private $organizations;
  private $organizations_top;
  private $schools;

  public function __construct($db, $cache) {
    $this->db = $db;
    $this->cache = $cache;
  }
  
  public function loadOrganization() {
    // Get all organizations in an array with [organization_id] = organization_array
    $organization_results = $this->db->db_select('CORE_ORGANIZATION', 'org', array('target' => 'schema'))
      ->fields('org', array('ORGANIZATION_ID', 'PARENT_ORGANIZATION_ID', 'ORGANIZATION_NAME', 'ORGANIZATION_ABBREVIATION', 'ORGANIZATION_TYPE', 'TARGET'))
      ->orderBy('PARENT_ORGANIZATION_ID')
      ->orderBy('ORGANIZATION_NAME')
      ->execute();
    while ($organization_row = $organization_results->fetch()) {
      
      // Create organization
      $this->organizations[$organization_row['ORGANIZATION_ID']] = $organization_row;
      $this->organizations[$organization_row['ORGANIZATION_ID']]['orgterms'] = array();
      $this->organizations[$organization_row['ORGANIZATION_ID']]['terms'] = array();
      $this->organizations[$organization_row['ORGANIZATION_ID']]['schools'] = array();
      
      // set children
      if ($organization_row['PARENT_ORGANIZATION_ID']) {
        $this->organizations[$organization_row['PARENT_ORGANIZATION_ID']]['children'][] = $organization_row['ORGANIZATION_ID'];
      } else {
        // top
        $this->organizations_top[] = $organization_row['ORGANIZATION_ID'];
      }
      
      if ($organization_row['ORGANIZATION_TYPE'] == 'S') {
        $this->schools[] = $organization_row['ORGANIZATION_ID'];
      }
      
    }
    
    // Get terms
    $orgTerms = $this->db->db_select('CORE_ORGANIZATION_TERMS', 'orgterms', array('target' => 'schema'))
        ->fields('orgterms')
        ->join('CORE_TERM', 'terms', 'terms.TERM_ID = orgterms.TERM_ID')
        ->orderBy('START_DATE', 'ASC', 'terms')
        ->execute();
    while ($orgTermRow = $orgTerms->fetch()) {
      $this->organizations[$orgTermRow['ORGANIZATION_ID']]['orgterms'][$orgTermRow['ORGANIZATION_TERM_ID']] = $orgTermRow['TERM_ID'];
      $this->organizations[$orgTermRow['ORGANIZATION_ID']]['terms'][] = $orgTermRow['TERM_ID'];
    }
    
    // loop through schools to propogate orgterms
    foreach($this->schools as $school) {
      $this->loadOrgTermsForParent($this->organizations[$school]['PARENT_ORGANIZATION_ID'], $this->organizations[$school]['orgterms']);
      $this->loadSchoolsForParent($this->organizations[$school]['PARENT_ORGANIZATION_ID'], $school);
      foreach($this->organizations[$school]['terms'] as $term) {
        $this->loadTermsForParent($this->organizations[$school]['PARENT_ORGANIZATION_ID'], $term);
      }
    }
  
    $this->cache->add('organization.top', $this->organizations_top);
    $this->cache->add('organization.schools', $this->schools);
    // Loop through navigation
    foreach($this->organizations as $id => $org) {
      $this->cache->add('organization.'.$id, $org);
    } // end foreach
    
    return array('organization' => $this->organizations, 'organizations_top' => $this->organizations_top, 'schools' => $this->schools);
  }
  
  private function loadOrgTermsForParent($parent_id, $orgterms) {
    if ($parent_id) {
      foreach($orgterms as $orgtermid => $termid) {
        $this->organizations[$parent_id]['orgterms'][$orgtermid] = $termid;
      }
      if ($this->organizations[$parent_id]['PARENT_ORGANIZATION_ID']) {
        $this->loadOrgTermsForParent($this->organizations[$parent_id]['PARENT_ORGANIZATION_ID'], $orgterms);
      }
    }
  }
  
  private function loadSchoolsForParent($parent_id, $school_id) {
    if ($parent_id) {
      if (!in_array($school_id, $this->organizations[$parent_id]['schools'])) {
        $this->organizations[$parent_id]['schools'][] = $school_id;
      }
      if ($this->organizations[$parent_id]['PARENT_ORGANIZATION_ID']) {
        $this->loadSchoolsForParent($this->organizations[$parent_id]['PARENT_ORGANIZATION_ID'], $school_id);
      }
    }
  }
  
  private function loadTermsForParent($parent_id, $term_id) {
    if ($parent_id) {
      if (!in_array($term_id, $this->organizations[$parent_id]['terms'])) {
        $this->organizations[$parent_id]['terms'][] = $term_id;
      }
      if ($this->organizations[$parent_id]['PARENT_ORGANIZATION_ID']) {
        $this->loadTermsForParent($this->organizations[$parent_id]['PARENT_ORGANIZATION_ID'], $term_id);
      }
    }
  }
  
}