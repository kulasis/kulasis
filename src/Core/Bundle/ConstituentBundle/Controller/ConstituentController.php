<?php

namespace Kula\Core\Bundle\ConstituentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class ConstituentController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Constituent');

    $constituent = array();
    
    $constituent = $this->db()->db_select('CONS_CONSTITUENT', 'constituent')
      ->fields('constituent')
      ->condition('CONSTITUENT_ID', $this->record->getSelectedRecord()['CONSTITUENT_ID'])
      ->execute()->fetch();
    
    return $this->render('KulaCoreConstituentBundle:Constituent:index.html.twig', array('constituent' => $constituent));
  }

  public function relationshipsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Constituent');

    $related = array();
    $siblings = array();
    
    if ($this->record->getSelectedRecordID()) {

      $db_or = $this->db()->db_or()
        ->condition('conrel.CONSTITUENT_ID', $this->record->getSelectedRecord()['CONSTITUENT_ID'])
        ->condition('conrel.RELATED_CONSTITUENT_ID', $this->record->getSelectedRecord()['CONSTITUENT_ID']);
    
      $related = $this->db()->db_select('CONS_RELATIONSHIP', 'conrel')
        ->fields('conrel', array('RELATIONSHIP', 'RELATIONSHIP_ID'))
        ->join('CONS_CONSTITUENT', 'con', 'con.CONSTITUENT_ID = conrel.RELATED_CONSTITUENT_ID OR con.CONSTITUENT_ID = conrel.CONSTITUENT_ID')
        ->fields('con', array('LAST_NAME', 'FIRST_NAME', 'GENDER', 'CONSTITUENT_ID'))
        ->condition($db_or)
        ->condition('con.CONSTITUENT_ID', $this->record->getSelectedRecord()['CONSTITUENT_ID'], '!=')
        ->execute()->fetchAll();

      // Get siblings
      // Get parents
      $parents = array();
      $parents_result = $this->db()->db_select('CONS_RELATIONSHIP', 'conrel')
        ->fields('conrel', array('RELATED_CONSTITUENT_ID'))
        ->condition('conrel.CONSTITUENT_ID', $this->record->getSelectedRecord()['CONSTITUENT_ID'])
        ->execute();
      while ($parent_row = $parents_result->fetch()) {
        $parents[] = $parent_row['RELATED_CONSTITUENT_ID'];
      }

      // Get related children
      if (count($parents) > 0) {
      $siblings = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
        ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'CONSTITUENT_ID', 'PERMANENT_NUMBER', 'GENDER'))
        ->join('CONS_RELATIONSHIP', 'conrel', 'conrel.CONSTITUENT_ID = cons.CONSTITUENT_ID')
        ->condition('conrel.RELATED_CONSTITUENT_ID', $parents)
        ->condition('conrel.CONSTITUENT_ID', $this->record->getSelectedRecord()['CONSTITUENT_ID'], '!=')
        ->execute()->fetchAll();
      }
    }
    
    return $this->render('KulaCoreConstituentBundle:Constituent:relationships.html.twig', array('related' => $related, 'siblings' => $siblings));
  }
  
  public function combineAction() {
    $this->authorize();
    
    $combine = $this->request->request->get('non');
    if (isset($combine['Core.Constituent']['delete']['Core.Constituent.ID']['value']) AND isset($combine['Core.Constituent']['keep']['Core.Constituent.ID']['value'])) {
      
      if ($result = $this->get('kula.Core.Combine')->combine('CONS_CONSTITUENT', $combine['Core.Constituent']['delete']['Core.Constituent.ID']['value'], $combine['Core.Constituent']['keep']['Core.Constituent.ID']['value'])) {
        $this->addFlash('success', 'Combined constitutents.');
      } else {
        $this->addFlash('error', 'Unable to combined constitutents.');
      }
      
    }

    return $this->render('KulaCoreConstituentBundle:Constituent:combine.html.twig');
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.Constituent')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
  public function add_constituentAction() {
    $this->authorize();
    $this->formAction('Core_Constituent_create_constituent');
    return $this->render('KulaCoreConstituentBundle:Constituent:add_constituent.html.twig');
  }
  
  public function create_constituentAction() {
    $this->authorize();
    
    $transaction = $this->db()->db_transaction();
    
    // get constituent data
    $constituent_addition = $this->form('add', 'Core.Constituent', 'new');
      
    // get next Student Number
    $constituent_addition['Core.Constituent.PermanentNumber'] = $this->get('kula.core.sequence')->getNextSequenceForKey('PERMANENT_NUMBER');

    $constituent_poster = $this->newPoster()->add('Core.Constituent', 'new', $constituent_addition)->process()->getResult();
    
    if ($constituent_poster) {
      $transaction->commit();
      return $this->forward('Core_Constituent_Constituent', array('record_type' => 'Core.Constituent', 'record_id' => $constituent_poster), array('record_type' => 'Core.Constituent', 'record_id' => $constituent_poster));
    } else {
      $transaction->rollback();
      throw new \Kula\Core\Component\DB\PosterException('Changes not saved.');  
    }
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.Constituent');
    
    $rows_affected = $this->newPoster()->delete('Core.Constituent', $this->record->getSelectedRecordID())->process()->getResult();
    
    if ($rows_affected == 1) {
      $this->addFlash('success', 'Deleted constituent.');
    }
    
    return $this->forward('Core_Constituent_Constituent');
  }
  
}
