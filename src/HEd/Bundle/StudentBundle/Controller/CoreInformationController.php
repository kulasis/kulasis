<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreInformationController extends Controller {
  
  public function basicAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $status = array();
    $addresses = array();
    $phones = array();
    $emails = array();
    $areas = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $selected_record = $this->record->getSelectedRecord();
      if (isset($selected_record['STUDENT_STATUS_ID'])) {
      
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'ADVISOR_ID', 'COHORT', 'ADMISSIONS_COUNSELOR_ID'))
        ->leftJoin('STUD_STUDENT_DEGREES', 'studegree', 'stustatus.SEEKING_DEGREE_1_ID = studegree.STUDENT_DEGREE_ID')
        ->fields('studegree', array('STUDENT_DEGREE_ID', 'DEGREE_ID', 'EXPECTED_COMPLETION_TERM_ID', 'GRADUATION_DATE'))
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecord()['STUDENT_STATUS_ID'])
        ->execute()->fetch();

      $areas = $this->db()->db_select('STUD_STUDENT_DEGREES_AREAS')
        ->fields('STUD_STUDENT_DEGREES_AREAS', array('STUDENT_AREA_ID', 'STUDENT_DEGREE_ID', 'AREA_ID'))
        ->condition('STUDENT_DEGREE_ID', $status['STUDENT_DEGREE_ID'])
        ->execute()->fetchAll();

      }
      
      $addresses = $this->db()->db_select('CONS_ADDRESS', null)
        ->fields(null, array('ADDRESS_ID', 'ADDRESS_TYPE', 'EFFECTIVE_DATE', 'THOROUGHFARE', 'LOCALITY', 'ADMINISTRATIVE_AREA', 'POSTAL_CODE', 'COUNTRY'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('ACTIVE', 1)
        ->orderBy('ADDRESS_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute()->fetchAll();
      
      $phones = $this->db()->db_select('CONS_PHONE', null)
        ->fields(null, array('PHONE_NUMBER_ID', 'EFFECTIVE_DATE', 'PHONE_TYPE', 'PHONE_NUMBER', 'PHONE_EXTENSION', 'PHONE_COUNTRY'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('ACTIVE', 1)
        ->orderBy('PHONE_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute()->fetchAll();
      
      $emails = $this->db()->db_select('CONS_EMAIL_ADDRESS', null)
        ->fields(null, array('EMAIL_ADDRESS_ID', 'EMAIL_ADDRESS_TYPE', 'EFFECTIVE_DATE', 'EMAIL_ADDRESS'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('ACTIVE', 1)
        ->orderBy('EMAIL_ADDRESS_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute()->fetchAll();
      
    } // end if selected record
    
    return $this->render('KulaHEdStudentBundle:CoreInformation:basic.html.twig', array('status' => $status, 'addresses' => $addresses, 'phones' => $phones, 'emails' => $emails, 'areas' => $areas));
  }
  
  public function demographicAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $student = array();
    
    $student = $this->db()->db_select('STUD_STUDENT')
      ->fields('STUD_STUDENT')
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = STUD_STUDENT.STUDENT_ID')
      ->fields('constituent')
      ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
      ->execute()->fetch();
    
    return $this->render('KulaHEdStudentBundle:CoreInformation:demographic.html.twig', array('student' => $student));
  }
  
  public function other_infoAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $student = array();
    
    $student = $this->db()->db_select('STUD_STUDENT', 'STUD_STUDENT')
      ->fields('STUD_STUDENT')
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = STUD_STUDENT.STUDENT_ID')
      ->fields('cons', array('NOTES'))
      ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
      ->execute()->fetch();

    if ($this->record->getSelectedRecordID()) {
    
      $student_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus')
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecord()['STUDENT_STATUS_ID'])
        ->execute()->fetch();
    }
    
    return $this->render('KulaHEdStudentBundle:CoreInformation:other_info.html.twig', array('student' => $student, 'student_status' => $student_status));
  }
  
  public function addAction() {
    $this->authorize();
	if ($this->request->request->get('search') == '') {
	  $this->setSubmitMode('search');
	}
    
    $constituents = array();
    
    if ($this->form('add', 'HEd.Student.Status', 'new', 'HEd.Student.Status.StudentID')) {
      $student_id = $this->createStudent();
      return $this->forward('core_HEd_student_information_basic', array('record_type' => 'Core.HEd.Student', 'record_id' => $student_id), array('record_type' => 'Core.HEd.Student', 'record_id' => $student_id));
    }
    
    
    if ($this->request->request->get('search')) {
      $query = $this->searcher->prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
      $query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'BIRTH_DATE', 'GENDER'));
      //$query = $query->expression(array("AES_DECRYPT(SOCIAL_SECURITY_NUMBER, '".$GLOBALS['ssn_key']."')" => 'SOCIAL_SECURITY_NUMBER'));
      $query = $query->leftJoin('STUD_STUDENT', 'stu', 'stu.STUDENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
      $query = $query->leftJoin('STUD_STUDENT_STATUS', 'status', 'stu.STUDENT_ID = status.STUDENT_ID AND status.ORGANIZATION_TERM_ID IN (' . implode(', ', $this->focus->getOrganizationTermIDs()) . ')');
      $query = $query->fields('status', array('ORGANIZATION_TERM_ID', 'STUDENT_STATUS_ID'));
      $query = $query->orderBy('LAST_NAME', 'ASC');
      $query = $query->orderBy('FIRST_NAME', 'ASC');
      $query = $query->range(0, 100);
      $constituents = $query->execute()->fetchAll();
    }
    
    $defaults = $this->db()->db_select('STUD_SCHOOL')
      ->fields('STUD_SCHOOL')
      ->condition('SCHOOL_ID', $this->focus->getOrganizationID())
      ->execute()->fetch();
    
    return $this->render('KulaHEdStudentBundle:CoreInformation:add.html.twig', array('constituents' => $constituents, 'defaults' => $defaults));
  }
  
  public function add_constituentAction() {
    $this->authorize();
    $this->formAction('core_HEd_student_information_create_constituent');
    
    $defaults = $this->db()->db_select('STUD_SCHOOL')
      ->fields('STUD_SCHOOL')
      ->condition('SCHOOL_ID', $this->focus->getOrganizationID())
      ->execute()->fetch();
    
    return $this->render('KulaHEdStudentBundle:CoreInformation:add_constituent.html.twig', array('defaults' => $defaults));
  }
  
  public function create_constituentAction() {
    $this->authorize();
    
    if ($student_id = $this->createStudent()) {
    
    return $this->forward('core_HEd_student_information_basic', array('record_type' => 'Core.HEd.Student', 'record_id' => $student_id), array('record_type' => 'Core.HEd.Student', 'record_id' => $student_id));
    
    }
  }
  
  private function createStudent() {
    
    $transaction = $this->db()->db_transaction();
    
    $studentInfo['EnterTerm'] = $this->focus->getTermID();
    $studentInfo = array_merge($studentInfo, $this->form('add', 'HEd.Student.Status', 'new'));
    
    $student_id = $this->get('kula.HEd.student')->addStudent(
      $this->form('add', 'HEd.Student.Status', 'new', 'HEd.Student.Status.StudentID'), 
      $this->form('add', 'Core.Constituent', 'new'),
      $studentInfo);
    
    $student_degree_id = $this->get('kula.HEd.student')->addDegree(array(
      'StudentID' => $student_id,
      'DegreeID' => $this->form('add', 'HEd.Student.Degree', 'new', 'HEd.Student.Degree.DegreeID'),
      'EffectiveDate' => $this->form('add', 'HEd.Student.Status', 'new', 'EnterDate'),
      'ExpectedCompletionTermID' => $this->form('add', 'HEd.Student.Degree', 'new', 'HEd.Student.Degree.ExpectedCompletionTermID')['value']
    ));
    
    $enrollmentInfo = array('StudentID' => $student_id, 
                            'SeekingDegree1ID' => $student_degree_id, 
                            'OrganizationTermID' => $this->focus->getOrganizationTermID(),
                            'EnterTermID' => $this->focus->getTermID()
                      );
    $enrollmentInfo = array_merge($enrollmentInfo, $this->form('add', 'HEd.Student.Status', 'new'));
    $enrollment = $this->get('kula.HEd.student')->enrollStudent($enrollmentInfo);
    
    $this->get('kula.HEd.billing.constituent')->determineTuitionRate($enrollment['student_status']);
    
    $this->get('kula.HEd.billing.student')->checkMandatoryTransactions($enrollment['student_status']);
    
    if ($enrollment['enrollment_activity_id']) {
      $transaction->commit();
      $this->addFlash('success', 'Added student.');
      return $student_id;
    } else {
      $transaction->rollback();
      throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
    }
    
  }
  
  public function index_teacherAction() {
    $this->authorize();
    return $this->render('KulaHEdStudentBundle:CoreInformation:index_teacher.html.twig');
    
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.HEd.Student')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}