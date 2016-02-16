<?php

namespace Kula\K12\Bundle\SchoolBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreStaffController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Staff');
    
    $staff = array();
    if ($this->record->getSelectedRecordID()) {
      // Get Staff
      $staff = $this->db()->db_select('STUD_STAFF', 'staff')
        ->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME', 'CONV_STAFF_NUMBER'))
        ->condition('staff.STAFF_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
    }
    
    return $this->render('KulaK12SchoolBundle:CoreStaff:index.html.twig', array('staff' => $staff));
  }
  
  public function staff_orgtermsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Staff');
    
    $stafforgterms = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
      ->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID', 'ORGANIZATION_TERM_ID', 'CONV_STAFF_NUMBER'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stafforgtrm.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'orgterms.TERM_ID = term.TERM_ID')
      ->condition('stafforgtrm.STAFF_ID', $this->record->getSelectedRecordID())
      ->orderBy('term.START_DATE', 'DESC')
      ->execute()->fetchAll();
    
    return $this->render('KulaK12SchoolBundle:CoreStaff:orgterms.html.twig', array('stafforgterms' => $stafforgterms));  
  }
  
  public function staff_scheduleAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Staff.SchoolTerm');
    
    $classes = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $classes = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
      ->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', null, 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_SECTION_MEETINGS', 'meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'), 'meetings.SECTION_ID = section.SECTION_ID')
      ->leftJoin('STUD_ROOM', 'rooms', array('ROOM_NUMBER'), 'rooms.ROOM_ID = meetings.ROOM_ID')
      ->leftJoin('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
      ->condition('section.STAFF_ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();
    
      foreach($classes as $key => $class) {
        $classes[$key]['meets'] = '';
        if ($class['MON'] == 'Y') $classes[$key]['meets'] .= 'M';
        if ($class['TUE'] == 'Y') $classes[$key]['meets'] .= 'T';
        if ($class['WED'] == 'Y') $classes[$key]['meets'] .= 'W';
        if ($class['THU'] == 'Y') $classes[$key]['meets'] .= 'R';
        if ($class['FRI'] == 'Y') $classes[$key]['meets'] .= 'F';
        if ($class['SAT'] == 'Y') $classes[$key]['meets'] .= 'S';
        if ($class['SUN'] == 'Y') $classes[$key]['meets'] .= 'U';
      }
    
    }
    
    return $this->render('schedule.html.twig', array('classes' => $classes));  
  }
  
  public function staff_chooserAction() {
    $this->authorize();
    $data = $this->chooser('K12.Staff')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
  public function staff_organizationterm_chooserAction() {
    $this->authorize();
    $data = $this->chooser('K12.Staff.SchoolTerm')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
  public function addAction() {
    $this->authorize();
    $this->setSubmitMode('search');
    
    $constituents = array();
    
    if ($staff_orgterm_addition = $this->form('add', 'K12.Staff.OrganizationTerm', 'new')) {
      $staff_exists = $this->db()->db_select('STUD_STAFF')
        ->fields('STUD_STAFF', array('STAFF_ID'))
        ->condition('STAFF_ID', $staff_orgterm_addition['K12.Staff.OrganizationTerm.StaffID'])
        ->execute()->fetch();
      if ($staff_exists['STAFF_ID'] == '') {
        // get staff data
        $staff_addition = $this->form('add', 'K12.Staff', 'new');
        $staff_poster = $this->newPoster()->add('K12.Staff', 'new', array(
          'K12.Staff.ID' => $staff_orgterm_addition['K12.Staff.OrganizationTerm.StaffID'],
          'K12.Staff.AbbreviatedName' => $staff_addition['K12.Staff.AbbreviatedName']
        ))->process();
      }
      // Add organization term staff
      $staff_orgterm_addition['K12.Staff.OrganizationTerm.OrganizationTermID'] = $this->focus->getOrganizationTermID();

      // Post data
      $staff_orgterm_poster = $this->newPoster()->add('K12.Staff.OrganizationTerm', 'new', $staff_orgterm_addition)->process()->getResult();
      $this->addFlash('success', 'Added staff.');
      return $this->forward('sis_K12_school_staff', array('record_type' => 'Core.K12.Staff', 'record_id' => $staff_orgterm_addition['K12.Staff.OrganizationTerm.StaffID']), array('record_type' => 'Core.K12.Staff', 'record_id' => $staff_orgterm_addition['K12.Staff.OrganizationTerm.StaffID']));
    }
    
    if ($this->request->request->get('search')) {
      $query = $this->searcher->prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
      $query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'));
      $query = $query->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
      $query = $query->condition('stafforgterm.STAFF_ORGANIZATION_TERM_ID', null);
      $query = $query->orderBy('LAST_NAME', 'ASC');
      $query = $query->orderBy('FIRST_NAME', 'ASC');
      $query = $query->range(0, 100);
      $constituents = $query->execute()->fetchAll();
    }
    
    return $this->render('KulaK12SchoolBundle:CoreStaff:add.html.twig', array('constituents' => $constituents));
  }
  
  public function add_constituentAction() {
    $this->authorize();
    $this->formAction('sis_K12_school_staff_create_constituent');
    return $this->render('KulaK12SchoolBundle:CoreStaff:add_constituent.html.twig');
  }
  
  public function create_constituentAction() {
    $this->authorize();
    
    $transaction = $this->db()->db_transaction();
    
    // get constituent data
    $constituent_addition = $this->form('add', 'Core.Constituent', 'new');
    $staff_addition = $this->form('add', 'K12.Staff', 'new');
    
    $constituent_poster = $this->newPoster()->add('Core.Constituent', 'new', $constituent_addition)->process()->getResult();
    
    $staff_addition['K12.Staff.ID'] = $constituent_poster;
    // Post data
    $staff_poster = $this->newPoster()->add('K12.Staff', 'new', $staff_addition)->process()->getResult();
    
    // Add organization term staff
    $staff_orgterm_poster = $this->newPoster()->add('K12.Staff.OrganizationTerm', 'new', array(
      'K12.Staff.OrganizationTerm.StaffID' => $constituent_poster,
      'K12.Staff.OrganizationTerm.OrganizationTermID' => $this->focus->getOrganizationTermID()
    ))->process()->getResult();
    
    if ($staff_orgterm_poster) {
      $transaction->commit();
      return $this->forward('sis_K12_school_staff', array('record_type' => 'K12.Staff', 'record_id' => $constituent_poster), array('record_type' => 'K12.Staff', 'record_id' => $constituent_poster));
    } else {
      $transaction->rollback();
      throw new \Kula\Core\Component\DB\PosterException('Changes not saved.');  
    }
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Staff.SchoolTerm');
    
    $rows_affected = $this->newPoster()->delete('K12.Staff.OrganizationTerm', $this->record->getSelectedRecordID())->process()->getResult();
    
    if ($rows_affected == 1) {
      $this->addFlash('success', 'Deleted staff from organization term.');
    }
    
    return $this->forward('sis_K12_school_staff');
  }
  
  
}