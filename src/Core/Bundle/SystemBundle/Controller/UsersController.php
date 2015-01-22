<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class UsersController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('USER');
		
		$user = $this->db()->select('CORE_USER', 'user')
			->predicate('USER_ID', $this->record->getSelectedRecordID())
			->execute()->fetch();
		
		return $this->render('KulaSystemBundle:Users:index.html.twig', array('user' => $user));
	}
	
	public function user_groupsAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('USER');		
		
		$usergroups = $this->db()->select('CORE_USER_ROLES', 'usrgrps')
			->fields('usrgrps', array('ROLE_ID', 'ORGANIZATION_ID', 'TERM_ID', 'ROLE_DEFAULT', 'ADMINISTRATOR'))
			->join('CORE_USERGROUP', 'usrgrp', array('USERGROUP_ID'), 'usrgrps.USERGROUP_ID = usrgrp.USERGROUP_ID')
			->predicate('USER_ID', $this->record->getSelectedRecordID())
			->order_by('USERGROUP_NAME', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaSystemBundle:Users:user_groups.html.twig', array('usergroups' => $usergroups));
	}
	
	public function addAction() {
		$this->authorize();
		$this->setSubmitMode($this->tpl, 'search');
		$constituents = array();
		
		if ($this->request->request->get('add')['CORE_USER']['new']['USER_ID']) {
			$this->processForm();
			$user_id = $this->poster->getResultForTable('insert', 'CORE_USER')['new'];
			return $this->forward('sis_system_users', array('record_type' => 'USER', 'record_id' => $user_id), array('record_type' => 'USER', 'record_id' => $user_id));
		}
		
		if ($this->request->request->get('search')) {
			$query = \Kula\Component\Database\Searcher::prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
			$query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'));
			$query = $query->left_join('CORE_USER', 'user', null, 'user.USER_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
			$query = $query->predicate('user.USER_ID', null);
			$query = $query->order_by('LAST_NAME', 'ASC');
			$query = $query->order_by('FIRST_NAME', 'ASC');
			$query = $query->range(0, 100);
			$constituents = $query->execute()->fetchAll();
		}
		
		return $this->render('KulaSystemBundle:Users:add.html.twig', array('constituents' => $constituents));
	}
	
	public function add_constituentAction() {
		$this->authorize();
		$this->formAction('sis_system_users_create_constituent');
		return $this->render('KulaSystemBundle:Users:add_constituent.html.twig');
	}
	
	public function create_constituentAction() {
		$this->authorize();
		$connect = \Kula\Component\Database\DB::connect('write');
		
		$connect->beginTransaction();
		// get constituent data
		$constituent_addition = $this->request->request->get('add')['CONS_CONSTITUENT'];
		// Post data
		$constituent_poster = new \Kula\Component\Database\Poster(array('CONS_CONSTITUENT' => $constituent_addition));
		// Get new constituent ID
		$constituent_id = $constituent_poster->getResultForTable('insert', 'CONS_CONSTITUENT')['new'];
		// get user data
		$user_addition = $this->request->request->get('add')['CORE_USER'];
		$user_addition['new']['USER_ID'] = $constituent_id;
		// Post data
		$user_poster = new \Kula\Component\Database\Poster(array('CORE_USER' => $user_addition));
		// Get user ID
		$user_id = $user_poster->getResultForTable('insert', 'CORE_USER')['new'];
		if ($user_id) {
			$connect->commit();
			return $this->forward('sis_system_users', array('record_type' => 'USER', 'record_id' => $user_id), array('record_type' => 'USER', 'record_id' => $user_id));
		} else {
			$connect->rollback();
			throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
		}
	}
	
	public function deleteAction() {
		$this->authorize();
		$this->setRecordType('USER');
		
		$rows_affected = $this->db()->delete('CORE_USER')
				->predicate('USER_ID', $this->record->getSelectedRecordID())->execute();
		
		if ($rows_affected == 1) {
			$this->flash->add('success', 'Deleted user.');
		}
		
		return $this->forward('sis_system_users');
	}
	
}