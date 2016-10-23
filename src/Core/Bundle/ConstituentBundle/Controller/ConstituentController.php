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
