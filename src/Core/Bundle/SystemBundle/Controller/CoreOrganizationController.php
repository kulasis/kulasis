<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreOrganizationController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    // Get organizations
    $organizations = $this->db()->db_select('CORE_ORGANIZATION', 'org')
      ->fields('org')
      ->orderBy('PARENT_ORGANIZATION_ID', 'ASC', 'org')
      ->orderBy('ORGANIZATION_NAME', 'ASC', 'org')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:Organization:index.html.twig', array('organizations' => $organizations));
  }
  
  public function orgterms_indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School');
    
    $orgterms = array();
    
    // Get terms
    if ($this->record->getSelectedRecordID()) {
    $orgterms = $this->db()->db_select('CORE_ORGANIZATION_TERMS', 'orgterms')
      ->fields('orgterms', array('TERM_ID', 'ORGANIZATION_TERM_ID'))
      ->join('CORE_TERM', 'terms', 'orgterms.TERM_ID = terms.TERM_ID')
      ->fields('terms', array('TERM_NAME', 'TERM_ABBREVIATION'))
      ->condition('ORGANIZATION_ID', $this->record->getSelectedRecordID())
      ->orderBy('START_DATE', 'DESC', 'terms')
      ->orderBy('TERM_NAME', 'ASC', 'terms')
      ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:Organization:org_terms.html.twig', array('orgterms' => $orgterms));
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.Organization')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
  public function org_term_chooserAction() {
    $this->authorize();
    $data = \Kula\Core\Bundle\SystemBundle\Chooser\OrganizationTermChooser::createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
}