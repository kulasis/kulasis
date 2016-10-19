<?php

namespace Kula\Core\Bundle\ConstituentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class ConstituentController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Constituent');
    
    return $this->render('KulaCoreConstituentBundle:Constituent:index.html.twig');
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
    $this->formAction('core_HEd_school_staff_create_constituent');
    return $this->render('KulaCoreConstituentBundle:Constituent:add_constituent.html.twig');
  }
  
  public function create_constituentAction() {
    $this->authorize();
    
    $transaction = $this->db()->db_transaction();
    
    // get constituent data
    $constituent_addition = $this->form('add', 'Core.Constituent', 'new');
    $staff_addition = $this->form('add', 'HEd.Staff', 'new');
    
    $constituent_poster = $this->newPoster()->add('Core.Constituent', 'new', $constituent_addition)->process()->getResult();
    
    $staff_addition['HEd.Staff.ID'] = $constituent_poster;
    // Post data
    $staff_poster = $this->newPoster()->add('HEd.Staff', 'new', $staff_addition)->process()->getResult();
    
    // Add organization term staff
    $staff_orgterm_poster = $this->newPoster()->add('HEd.Staff.OrganizationTerm', 'new', array(
      'HEd.Staff.OrganizationTerm.StaffID' => $constituent_poster,
      'HEd.Staff.OrganizationTerm.OrganizationTermID' => $this->focus->getOrganizationTermID()
    ))->process()->getResult();
    
    if ($staff_orgterm_poster) {
      $transaction->commit();
      return $this->forward('core_HEd_school_staff', array('record_type' => 'Core.HEd.Staff', 'record_id' => $constituent_poster), array('record_type' => 'Core.HEd.Staff', 'record_id' => $constituent_poster));
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
    
    return $this->forward('core_Constituent_Constituent');
  }
  
}
