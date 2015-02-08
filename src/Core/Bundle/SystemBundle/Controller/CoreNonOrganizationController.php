<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreNonOrganizationController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    $nonorganizations = array();
    
    $nonorganizations = $this->db()->db_select('CORE_NON_ORGANIZATION')
      ->fields('CORE_NON_ORGANIZATION')
      ->orderBy('NON_ORGANIZATION_NAME')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:NonOrganization:index.html.twig', array('nonorganizations' => $nonorganizations));
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.NonOrganization')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}