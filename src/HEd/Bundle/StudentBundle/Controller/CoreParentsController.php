<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreParentsController extends Controller {
  
  public function studentParentsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    if ($this->form('delete', 'HEd.Student.Parent')) {
      $relationshipID = key($this->form('delete', 'HEd.Student.Parent'));
      $this->newPoster()->delete('Core.Constituent.Relationship', $relationshipID)->process()->getResult();
    }
    
    $parents = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $parents = $this->db()->db_select('STUD_STUDENT_PARENTS', 'stupar')
      ->fields('stupar')
      ->join('CONS_RELATIONSHIP', 'conrel', 'conrel.RELATIONSHIP_ID = stupar.STUDENT_PARENT_ID')
      ->fields('conrel', array('RELATIONSHIP'))
      ->join('CONS_CONSTITUENT', 'conpar', 'conpar.CONSTITUENT_ID = conrel.RELATED_CONSTITUENT_ID')
      ->fields('conpar', array('LAST_NAME', 'FIRST_NAME', 'CONSTITUENT_ID' => 'PARENT_ID'))
      ->condition('conrel.CONSTITUENT_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    }
  
    return $this->render('KulaHEdStudentBundle:CoreParents:studentParents.html.twig', array('parents' => $parents));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    $this->setSubmitMode('search');
    
    $constituents = array();
    
    if ($this->form('add', 'HEd.Student.Parent', 'new', 'HEd.Student.Parent.ID')) {
      $this->createParent();
      return $this->forward('core_HEd_student_information_parents', array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()));
    }
    
    
    if ($this->request->request->get('search')) {
	  $this->setSubmitMode('submit');
      $query = $this->searcher->prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
      $query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'BIRTH_DATE', 'GENDER'))->distinct();
      $query = $query->leftJoin('CONS_RELATIONSHIP', 'conrel', "conrel.RELATED_CONSTITUENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID AND conrel.CONSTITUENT_ID = ".$this->record->getSelectedRecordID());
      $query = $query->leftJoin('STUD_STUDENT_PARENTS', 'stupar', 'stupar.STUDENT_PARENT_ID = conrel.RELATIONSHIP_ID')->fields('stupar', array('STUDENT_PARENT_ID'));
      $query = $query->orderBy('LAST_NAME', 'ASC');
      $query = $query->orderBy('FIRST_NAME', 'ASC');
      $query = $query->range(0, 100);

      $constituents = $query->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:CoreParents:add.html.twig', array('constituents' => $constituents));
  }
  
  public function add_constituentAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    $this->formAction('Core_HEd_Student_Parents_Create_Constituent');
    return $this->render('KulaHEdStudentBundle:CoreParents:add_constituent.html.twig');
  }
  
  public function create_constituentAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    
    if ($this->createParent()) {
      return $this->forward('core_HEd_student_information_parents', array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()));
    
    }
  }
  
  private function createParent() {
    
    $transaction = $this->db()->db_transaction();
    
    $parentID = $this->form('add', 'HEd.Student.Parent', 'new', 'HEd.Student.Parent.ID');
    
    // Create CONS_CONSTITUENT record
    if ($parentID === null) {
      $parentInfo = $this->form('add', 'Core.Constituent', 'new');
      // get next Student Number
      $parentInfo['Core.Constituent.PermanentNumber'] = $this->get('kula.core.sequence')->getNextSequenceForKey('PERMANENT_NUMBER');
      $parentID = $this->newPoster()->add('Core.Constituent', 0, $parentInfo);

      $parentID = $parentID->process()->getID();
    }
    
    // Create STUD_PARENT record
    $parentRecord = $this->db()->db_select('STUD_PARENT', 'par')
      ->fields('par', array('PARENT_ID'))
      ->condition('par.PARENT_ID', $parentID)
      ->execute()->fetch();
    if (!$parentRecord['PARENT_ID']) {
      $this->newPoster()->add('HEd.Parent', 0, array('HEd.Parent.ID' => $parentID))->process()->getID();
    }
    
    // Create relationship in CONS_RELATIONSHIP
    $relationshipRecord = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('RELATIONSHIP_ID'))
      ->condition('rel.CONSTITUENT_ID', $this->record->getSelectedRecordID())
      ->condition('rel.RELATED_CONSTITUENT_ID', $parentID)
      ->execute()->fetch();     
    if (!$relationshipRecord['RELATIONSHIP_ID']) {
      $relationshipID = $this->newPoster()->add('Core.Constituent.Relationship', 0, array(
        'Core.Constituent.Relationship.ConstituentID' => $this->record->getSelectedRecordID(),
        'Core.Constituent.Relationship.RelatedConstituentID' => $parentID,
        'Core.Constituent.Relationship.Relationship' => $this->form('add', 'Core.Constituent.Relationship', $this->record->getSelectedRecordID(), 'Core.Constituent.Relationship.Relationship')
      ))->process()->getID();
    } else {
      $relationshipID = $relationshipRecord['RELATIONSHIP_ID'];
    }
    
    // Create student parent record in STUD_STUDENT_PARENTS
    $studentParentID = $this->newPoster()->add('HEd.Student.Parent', 0, array(
      'HEd.Student.Parent.ID' => $relationshipID
    ))->process()->isPosted();
    
    if ($studentParentID) {
      $transaction->commit();
      $this->addFlash('success', 'Added parent to student.');
      return $studentParentID;
    } else {
      $transaction->rollback();
      throw new \Kula\Core\Component\DB\PosterException('Changes not saved.');
    }
    
  }
  
}