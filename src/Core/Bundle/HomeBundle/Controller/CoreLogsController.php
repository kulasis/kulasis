<?php

namespace Kula\Bundle\Core\HomeBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class LogsController extends Controller {

	public function sessionAction() {
		
		$this->authorize();

		$sessions = array();

		$sessions = $this->db()->select('LOG_SESSION', 'session')
			->fields('session', array('SESSION_ID', 'IN_TIME', 'OUT_TIME', 'IP_ADDRESS'))
			->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME'), 'constituent.CONSTITUENT_ID = session.USER_ID')
			->join('CORE_USER_ROLES', 'role', null, 'role.ROLE_ID = session.ROLE_ID')
			->join('CORE_USERGROUP', 'usergroup', array('USERGROUP_NAME'), 'usergroup.USERGROUP_ID = role.USERGROUP_ID')
			->join('CORE_ORGANIZATION', 'organization', array('ORGANIZATION_ABBREVIATION'), 'organization.ORGANIZATION_ID = role.ORGANIZATION_ID')
			->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = session.TERM_ID')
			->order_by('IN_TIME', 'DESC', 'session')
			->range(0, 100);
		$sessions = $sessions->execute()->fetchAll();
		
		return $this->render('KulaHomeBundle:Logs:session.html.twig', array('sessions' => $sessions));
	}
	
}