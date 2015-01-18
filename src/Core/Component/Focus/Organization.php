<?php

namespace Kula\Core\Component\Focus;

class Organization {
  
  public $all_organizations;
  public $school_organization_ids;
  
  public function getAllOrganization() {
    return $this->all_organizations;
  }
  
  public function setOrganization($top_organization_id) {  
    $this->all_organizations = array();
    // Get all organizations in an array with [organization_id] = organization_array
    $organization_results = \Kula\Component\Database\DB::connect('read')->select('CORE_ORGANIZATION', 'org')
      ->fields('org', array('ORGANIZATION_ID', 'PARENT_ORGANIZATION_ID', 'ORGANIZATION_NAME', 'SCHOOL'))
      ->order_by('ORGANIZATION_NAME')
      ->execute();
    while ($organization_row = $organization_results->fetch()) {
      $this->all_organizations[$organization_row['ORGANIZATION_ID']] = $organization_row;
    }

    $this->extractSchoolsFromOrganization($top_organization_id, $this->all_organizations);
  }
  
  public function getSchoolOrganizationIDs() {
    return $this->school_organization_ids;
  }
  
  private function extractSchoolsFromOrganization($parent_organization_id, $organization_array) {
    
    if ($organization_array[$parent_organization_id]['SCHOOL'] == 'Y') {
      $this->school_organization_ids[] = $parent_organization_id;
    }
    
    // look for any children
    foreach($organization_array as $org_id => $org_row) {
      foreach($org_row as $org_row_key => $org_row_value) {
        if ($org_row_key == 'PARENT_ORGANIZATION_ID' && $org_row_value == $parent_organization_id) {
          $this->extractSchoolsFromOrganization($org_id, $organization_array);
        }
      } // end inner foreach
    } // end top foreach
  }
  
}