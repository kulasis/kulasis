<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class UsersController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.User');
    
    $user = $this->db()->db_select('CORE_USER', 'user')
      ->fields('user')
      ->condition('USER_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();
    
    return $this->render('KulaCoreSystemBundle:Users:index.html.twig', array('user' => $user));
  }
  
  public function user_groupsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.User');
    
    $usergroups = $this->db()->db_select('CORE_USER_ROLES', 'usrgrps')
      ->fields('usrgrps', array('ROLE_ID', 'ORGANIZATION_ID', 'TERM_ID', 'DEFAULT_ROLE', 'ADMINISTRATOR'))
      ->join('CORE_USERGROUP', 'usrgrp', 'usrgrps.USERGROUP_ID = usrgrp.USERGROUP_ID')
      ->fields('usrgrp', array('USERGROUP_ID'))
      ->condition('USER_ID', $this->record->getSelectedRecordID())
      ->orderBy('USERGROUP_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:Users:user_groups.html.twig', array('usergroups' => $usergroups));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setSubmitMode($this->tpl, 'search');
    $constituents = array();
    
    if ($this->request->request->get('add')['CORE_USER']['new']['USER_ID']) {
      $this->processForm();
      $user_id = $this->poster->getResultForTable('insert', 'CORE_USER')['new'];
      return $this->forward('core_system_users', array('record_type' => 'USER', 'record_id' => $user_id), array('record_type' => 'USER', 'record_id' => $user_id));
    }
    
    if ($this->request->request->get('search')) {
      $query = \Kula\Component\Database\Searcher::prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
      $query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'));
      $query = $query->leftJoin('CORE_USER', 'user', 'user.USER_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
      $query = $query->condition('user.USER_ID', null);
      $query = $query->orderBy('LAST_NAME', 'ASC');
      $query = $query->orderBy('FIRST_NAME', 'ASC');
      $query = $query->range(0, 100);
      $constituents = $query->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreSystemBundle:Users:add.html.twig', array('constituents' => $constituents));
  }
  
  public function add_constituentAction() {
    $this->authorize();
    $this->formAction('core_system_users_create_constituent');
    return $this->render('KulaCoreSystemBundle:Users:add_constituent.html.twig');
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
      return $this->forward('core_system_users', array('record_type' => 'USER', 'record_id' => $user_id), array('record_type' => 'USER', 'record_id' => $user_id));
    } else {
      $connect->rollback();
      throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
    }
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.User');
    
    $rows_affected = $this->db()->db_delete('CORE_USER')
        ->condition('USER_ID', $this->record->getSelectedRecordID())->execute();
    
    if ($rows_affected == 1) {
      $this->flash->add('success', 'Deleted user.');
    }
    
    return $this->forward('core_system_users');
  }
  
}