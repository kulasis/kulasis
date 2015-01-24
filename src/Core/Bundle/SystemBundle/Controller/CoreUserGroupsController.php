<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreUserGroupsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    // Get Fields
    $usergroups = $this->db()->db_select('CORE_USERGROUP')
      ->fields('CORE_USERGROUP')
      ->orderBy('USERGROUP_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:UserGroups:index.html.twig', array('usergroups' => $usergroups));
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.Usergroup')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
  
}