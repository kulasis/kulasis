<?php

namespace Kula\Core\Bundle\ConstituentBundle\Service;

class ConstituentService {
  
  public function __construct($db, $sequence, $poster) {
    $this->db = $db;
    $this->sequence = $sequence;
    $this->poster = $poster;
  }
  
  public function createConstituent($constituentInfo) {

    // get next Student Number
    $constituentInfo['Core.Constituent.PermanentNumber'] = $this->sequence->getNextSequenceForKey('PERMANENT_NUMBER');
    // Post data
    $constituentID = $this->poster->newPoster()->add('Core.Constituent', 0, $constituentInfo)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getID();
    
    // if ssn, update ssn
    if (isset($constituentInfo['Core.Constituent.SocialSecurityNumber']) AND $constituentInfo['Core.Constituent.SocialSecurityNumber'] != '') {
      $this->poster->newPoster()->edit('Core.Constituent', $constituentID, array('Core.Constituent.SocialSecurityNumber' => $constituentInfo['Core.Constituent.SocialSecurityNumber']))->process();
    }

    return $constituentID;

  }

  public function updateConstituent($constituentID, $constituentInfo) {

    // Post data
    $constituent = $this->poster->newPoster()->edit('Core.Constituent', $constituentID, $constituentInfo)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getResult();

    return $constituent;

  }
  
}