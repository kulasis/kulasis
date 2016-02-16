<?php

namespace Kula\Core\Component\Focus;

class Organization {
  
  public function __construct($cache) {
    $this->cache = $cache;
  }
      
  public function getOrganization($id) {
    if ($this->cache->exists('organization.'.$id)) {
      return $this->cache->get('organization.'.$id);
    }
  }
  
  public function getTarget($id) {
    if ($target = $this->getOrganization($id))
      return $target['TARGET'];
  }
  
  public function getName($id) {
    if ($target = $this->getOrganization($id))
      return $target['ORGANIZATION_NAME'];
  }
  
  public function getSchools($id) {
    if ($this->cache->exists('organization.'.$id) AND $this->cache->get('organization.'.$id)['ORGANIZATION_TYPE'] != 'S') {
      return $this->cache->get('organization.'.$id)['schools'];
    } elseif ($this->cache->exists('organization.'.$id) AND $this->cache->get('organization.'.$id)['ORGANIZATION_TYPE'] == 'S') {
      return array($id);
    }
  }
  
  public function getTermsForOrganization($id) {
    if ($this->cache->exists('organization.'.$id)) {
      return $this->cache->get('organization.'.$id)['terms'];
    }
  }
  
  public function getOrganizationTerms($organizationID, $termIDs) {
    
    $finalOrganizationTerms = array();
    
    if ($this->cache->exists('organization.'.$organizationID)) {
      $organizationTerms = $this->cache->get('organization.'.$organizationID)['orgterms'];
      
      foreach($organizationTerms as $organizationTerm => $termID) {
        
        if ((is_array($termIDs) AND in_array($termID, $termIDs)) OR (!is_array($termIDs) AND $termID == $termIDs)) {
          $finalOrganizationTerms[] = $organizationTerm;
        }
        
      }
    }
    
    return $finalOrganizationTerms;
  }
  
}