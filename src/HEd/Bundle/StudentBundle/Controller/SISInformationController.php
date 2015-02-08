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
  
  public function enrollmentAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student.Status');
    
    $status = array();
    $effective_date = null;
    
    if ($this->record->getSelectedRecordID()) {
      
      // Add enrollment activity
      if ($activity_post = $this->form('add', 'HEd.Student.Enrollment.Activity', 'new')) {
        
        // posted data
        $transaction = $this->db()->db_transaction();
        
        if ($activity_post['HEd.Student.Enrollment.Activity.Grade']) {
          $activity_data['HEd.Student.Enrollment.Activity.Grade'] = $activity_post['HEd.Student.Enrollment.Activity.Grade'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Resident']) {
          $activity_data['HEd.Student.Enrollment.Activity.Resident'] = $activity_post['HEd.Student.Enrollment.Activity.Resident'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.FTE']) {
          $activity_data['HEd.Student.Enrollment.Activity.FTE'] = $activity_post['HEd.Student.Enrollment.Activity.FTE'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Level']) {
          $activity_data['HEd.Student.Enrollment.Activity.Level'] = $activity_post['HEd.Student.Enrollment.Activity.Level'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.ThesisStatus']) {
          $activity_data['HEd.Student.Enrollment.Activity.ThesisStatus'] = $activity_post['HEd.Student.Enrollment.Activity.ThesisStatus'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.SeekingDegree1ID']) {
          $activity_data['HEd.Student.Enrollment.Activity.SeekingDegree1ID'] = $activity_post['HEd.Student.Enrollment.Activity.SeekingDegree1ID'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.SeekingDegree2ID']) {
          $activity_data['HEd.Student.Enrollment.Activity.SeekingDegree2ID'] = $activity_post['HEd.Student.Enrollment.Activity.SeekingDegree2ID'];
        }
        
        // Post data to status
        if ($activity_post['HEd.Student.Enrollment.Activity.Grade']) {
          $status_data['HEd.Student.Status.Grade'] = $activity_post['HEd.Student.Enrollment.Activity.Grade'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Resident']) {
          $status_data['HEd.Student.Status.Resident'] = $activity_post['HEd.Student.Enrollment.Activity.Resident'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.FTE']) {
          $status_data['HEd.Student.Status.FTE'] = $activity_post['HEd.Student.Enrollment.Activity.FTE'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Level']) {
          $status_data['HEd.Student.Status.Level'] = $activity_post['HEd.Student.Enrollment.Activity.Level'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.ThesisStatus']) {
          $status_data['HEd.Student.Status.ThesisStatus'] = $activity_post['HEd.Student.Enrollment.Activity.ThesisStatus'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.SeekingDegree1ID']) {
          $status_data['HEd.Student.Status.SeekingDegree1ID'] = $activity_post['HEd.Student.Enrollment.Activity.SeekingDegree1ID'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.SeekingDegree2ID']) {
          $status_data['HEd.Student.Status.SeekingDegree2ID'] = $activity_post['HEd.Student.Enrollment.Activity.SeekingDegree2ID'];
        }
        
        // Get latest enrollment ID
        $enrollment = $this->db()->db_select('STUD_STUDENT_ENROLLMENT')
          ->fields('STUD_STUDENT_ENROLLMENT', array('ENROLLMENT_ID', 'ENTER_DATE'))
          ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
          ->orderBy('ENTER_DATE', 'DESC')
          ->execute()->fetch();
        
        // Determine if date already exists
        $activity_exists = $this->db()->db_select('STUD_STUDENT_ENROLLMENT_ACTIVITY')
          ->fields('STUD_STUDENT_ENROLLMENT_ACTIVITY', array('EFFECTIVE_DATE', 'ENROLLMENT_ACTIVITY_ID'))
          ->condition('ENROLLMENT_ID', $enrollment['ENROLLMENT_ID'])
          ->orderBy('EFFECTIVE_DATE', 'DESC')
          ->execute()->fetch();
        if ($activity_exists['EFFECTIVE_DATE'] == date('Y-m-d', strtotime($activity_post['HEd.Student.Enrollment.Activity.EffectiveDate']))) {
          // update existing record
          // Post data to activity
          $activity_poster = $this->newPoster()->edit('HEd.Student.Enrollment.Activity', $activity_exists['ENROLLMENT_ACTIVITY_ID'], $activity_data)->process()->getResult();
        } else {
          // insert new record
          // Post data to activity
          $activity_data['HEd.Student.Enrollment.Activity.EffectiveDate'] = $activity_post['HEd.Student.Enrollment.Activity.EffectiveDate'];
          $activity_data['HEd.Student.Enrollment.Activity.EnrollmentID'] = $enrollment['ENROLLMENT_ID'];
          $activity_poster = $this->newPoster()->add('HEd.Student.Enrollment.Activity', 'new', $activity_data)->process()->getResult();
        }
       
        if ($activity_exists['EFFECTIVE_DATE'] <= date('Y-m-d', strtotime($activity_post['HEd.Student.Enrollment.Activity.EffectiveDate']))) {
          // Post data to status
          $status_poster = $this->newPoster()->edit('HEd.Student.Status', $this->record->getSelectedRecordID(), $status_data)->process()->getResult();
        }
        
        if ($activity_poster) {
          $transaction->commit();
          return $this->forward('sis_HEd_student_information_enrollment', array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
        } else {
          $transaction->rollback();
          throw new \Kula\Core\Component\DB\PosterException('Changes not saved.');
        }
      }
      
      $status = $this->db()->db_select('STUD_STUDENT_STATUS')
        ->fields('STUD_STUDENT_STATUS')
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      
      $effective_date = date('Y-m-d');
    
    } // end if selected record
        
    return $this->render('KulaHEdStudentBundle:SISInformation:enrollment.html.twig', array('status' => $status, 'effective_date' => $effective_date));
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
    
    $this->get('kula.HEd.billing.student')->determineTuitionRate($enrollment['student_status']);
    
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