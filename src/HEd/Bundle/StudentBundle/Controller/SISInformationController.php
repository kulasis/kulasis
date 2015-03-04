<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISInformationController extends Controller {
  
  public function basicAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $status = array();
    $addresses = array();
    $phones = array();
    $emails = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $selected_record = $this->record->getSelectedRecord();
      if (isset($selected_record['STUDENT_STATUS_ID'])) {
      
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'ADVISOR_ID', 'COHORT', 'ADMISSIONS_COUNSELOR_ID'))
        ->leftJoin('STUD_STUDENT_DEGREES', 'studegree', 'stustatus.SEEKING_DEGREE_1_ID = studegree.STUDENT_DEGREE_ID')
        ->fields('studegree', array('STUDENT_DEGREE_ID', 'DEGREE_ID', 'EXPECTED_COMPLETION_TERM_ID', 'GRADUATION_DATE'))
        ->leftJoin('STUD_STUDENT_DEGREES_MAJORS', 'stumajor', 'stumajor.STUDENT_DEGREE_ID = studegree.STUDENT_DEGREE_ID')
        ->fields('stumajor', array('MAJOR_ID'))
        ->leftJoin('STUD_STUDENT_DEGREES_MINORS', 'stuminor', 'stuminor.STUDENT_DEGREE_ID = studegree.STUDENT_DEGREE_ID')
        ->fields('stuminor', array('MINOR_ID'))
        ->leftJoin('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentration', 'stuconcentration.STUDENT_DEGREE_ID = studegree.STUDENT_DEGREE_ID')
        ->fields('stuconcentration', array('CONCENTRATION_ID'))
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecord()['STUDENT_STATUS_ID'])
        ->execute()->fetch();

      }
      
      $address_result = $this->db()->db_select('CONS_ADDRESS', null)
        ->fields(null, array('ADDRESS_ID', 'ADDRESS_TYPE', 'EFFECTIVE_DATE', 'THOROUGHFARE', 'LOCALITY', 'ADMINISTRATIVE_AREA', 'POSTAL_CODE', 'COUNTRY'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('ADDRESS_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute();
      $address_type = '';
      while ($address_row = $address_result->fetch()) {
        if ($address_type != $address_row['ADDRESS_TYPE'])
          $addresses[$address_row['ADDRESS_TYPE']] = $address_row;
        $address_type = $address_row['ADDRESS_TYPE'];
      }
      
      $phone_result = $this->db()->db_select('CONS_PHONE', null)
        ->fields(null, array('PHONE_NUMBER_ID', 'EFFECTIVE_DATE', 'PHONE_TYPE', 'PHONE_NUMBER', 'PHONE_EXTENSION', 'PHONE_COUNTRY'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('PHONE_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute();
      $phone_type = '';
      while ($phone_row = $phone_result->fetch()) {
        if ($phone_type != $phone_row['PHONE_TYPE'])
          $phones[$phone_row['PHONE_TYPE']] = $phone_row;
        $phone_type = $phone_row['PHONE_TYPE'];
      }
      
      $email_result = $this->db()->db_select('CONS_EMAIL_ADDRESS', null)
        ->fields(null, array('EMAIL_ADDRESS_ID', 'EMAIL_ADDRESS_TYPE', 'EFFECTIVE_DATE', 'EMAIL_ADDRESS'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('EMAIL_ADDRESS_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute();
      $email_type = '';
      $i = 0;
      while ($email_row = $email_result->fetch()) {
        if ($email_row['EMAIL_ADDRESS_TYPE'] == null)
          $email_row['EMAIL_ADDRESS_TYPE'] = 'OT' . $i;
        if ($email_type != $email_row['EMAIL_ADDRESS_TYPE'])
          $emails[$email_row['EMAIL_ADDRESS_TYPE']] = $email_row;
        $email_type = $email_row['EMAIL_ADDRESS_TYPE'];
        $i++;
      }
      
      
    } // end if selected record
    
    return $this->render('KulaHEdStudentBundle:SISInformation:basic.html.twig', array('status' => $status, 'addresses' => $addresses, 'phones' => $phones, 'emails' => $emails));
  }
  
  public function demographicAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $student = array();
    
    $student = $this->db()->db_select('STUD_STUDENT')
      ->fields('STUD_STUDENT')
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = STUD_STUDENT.STUDENT_ID')
      ->fields('constituent')
      ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
      ->execute()->fetch();
    
    return $this->render('KulaHEdStudentBundle:SISInformation:demographic.html.twig', array('student' => $student));
  }
  
  public function other_infoAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $student = array();
    
    $student = $this->db()->db_select('STUD_STUDENT', 'STUD_STUDENT')
      ->fields('STUD_STUDENT')
      ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
      ->execute()->fetch();
    
    return $this->render('KulaHEdStudentBundle:SISInformation:other_info.html.twig', array('student' => $student));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setSubmitMode('search');
    
    $constituents = array();
    
    if ($this->form('add', 'HEd.Student.Status', 'new', 'HEd.Student.Status.StudentID')) {
      $student_id = $this->createStudent();
      return $this->forward('sis_HEd_student_information_basic', array('record_type' => 'SIS.HEd.Student', 'record_id' => $student_id), array('record_type' => 'SIS.HEd.Student', 'record_id' => $student_id));
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
    
    return $this->render('KulaHEdStudentBundle:SISInformation:add.html.twig', array('constituents' => $constituents));
  }
  
  public function add_constituentAction() {
    $this->authorize();
    $this->formAction('sis_HEd_student_information_create_constituent');
    return $this->render('KulaHEdStudentBundle:SISInformation:add_constituent.html.twig');
  }
  
  public function create_constituentAction() {
    $this->authorize();
    
    if ($student_id = $this->createStudent()) {
    
    return $this->forward('sis_HEd_student_information_basic', array('record_type' => 'SIS.HEd.Student', 'record_id' => $student_id), array('record_type' => 'SIS.HEd.Student', 'record_id' => $student_id));
    
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
    return $this->render('KulaHEdStudentBundle:SISInformation:index_teacher.html.twig');
    
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('SIS.HEd.Student')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}