<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class UserGroupsController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		
		// Get Fields
		$usergroups = $this->db()->select('CORE_USERGROUP')
			->order_by('USERGROUP_NAME', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaSystemBundle:UserGroups:index.html.twig', array('usergroups' => $usergroups));
	}
	
	public function chooserAction() {
		$this->authorize();
		$data = \Kula\Bundle\Core\SystemBundle\Chooser\UserGroupsChooser::createChooserMenu($this->request->query->get('q'));
		return $this->JSONResponse($data);
	}
	
	
}