<?php

namespace Kula\Bundle\Core\SystemBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class NonOrganizationController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		
		$nonorganizations = array();
		
		$nonorganizations = $this->db()->select('CORE_NON_ORGANIZATION')
			->fields(null, array('NON_ORGANIZATION_ID', 'NON_ORGANIZATION_TYPE', 'NON_ORGANIZATION_NAME', 'CONV_NAME'))
			->order_by('NON_ORGANIZATION_NAME')
			->execute()->fetchAll();
		
		return $this->render('KulaSystemBundle:NonOrganization:index.html.twig', array('nonorganizations' => $nonorganizations));
	}
	
	public function chooserAction() {
		$this->authorize();
		$data = \Kula\Bundle\Core\SystemBundle\Chooser\NonOrganizationChooser::createChooserMenu($this->request->query->get('q'));
		return $this->JSONResponse($data);
	}
	
}