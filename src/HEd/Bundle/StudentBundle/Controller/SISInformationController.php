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
      if (isset($this->request->request->get('add')['STUD_STUDENT_ENROLLMENT_ACTIVITY']['new'])) {
        
        // posted data
        $activity_post = $this->request->request->get('add')['STUD_STUDENT_ENROLLMENT_ACTIVITY']['new'];
        
        $connect = $this->db('write');
        if (!$connect->inTransaction())
          $connect->beginTransaction();
        
        // Get latest enrollment ID
        $enrollment = $this->db()->db_select('STUD_STUDENT_ENROLLMENT')
          ->fields('STUD_STUDENT_ENROLLMENT', array('ENROLLMENT_ID', 'ENTER_DATE'))
          ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
          ->orderBy('ENTER_DATE', 'DESC')
          ->execute()->fetch();
        
        $activity_addition['EFFECTIVE_DATE'] = $activity_post['EFFECTIVE_DATE'];
        if ($activity_post['GRADE']) $activity_addition['GRADE'] = $activity_post['GRADE'];
        if ($activity_post['RESIDENT']) $activity_addition['RESIDENT'] = $activity_post['RESIDENT'];
        if ($activity_post['FTE']) $activity_addition['FTE'] = $activity_post['FTE'];
        if ($activity_post['LEVEL']) $activity_addition['LEVEL'] = $activity_post['LEVEL'];
        if ($activity_post['THESIS_STATUS']) $activity_addition['THESIS_STATUS'] = $activity_post['THESIS_STATUS'];
        if ($activity_post['SEEKING_DEGREE_1_ID']) $activity_addition['SEEKING_DEGREE_1_ID'] = $activity_post['SEEKING_DEGREE_1_ID'];
        if ($activity_post['SEEKING_DEGREE_2_ID']) $activity_addition['SEEKING_DEGREE_2_ID'] = $activity_post['SEEKING_DEGREE_2_ID'];
        
        // Determine if date already exists
        $activity_exists = $this->db()->db_select('STUD_STUDENT_ENROLLMENT_ACTIVITY')
          ->fields('STUD_STUDENT_ENROLLMENT_ACTIVITY', array('EFFECTIVE_DATE', 'ENROLLMENT_ACTIVITY_ID'))
          ->condition('ENROLLMENT_ID', $enrollment['ENROLLMENT_ID'])
          ->orderBy('EFFECTIVE_DATE', 'DESC')
          ->execute()->fetch();
        if ($activity_exists['EFFECTIVE_DATE'] == date('Y-m-d', strtotime($activity_post['EFFECTIVE_DATE']))) {
          // update existing record
          // Post data to activity
          $activity_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_ENROLLMENT_ACTIVITY' => array($activity_exists['ENROLLMENT_ACTIVITY_ID'] => $activity_addition)));
          $activity_poster_aff_rows = $activity_poster->getResultForTable('update', 'STUD_STUDENT_ENROLLMENT_ACTIVITY')[$activity_exists['ENROLLMENT_ACTIVITY_ID']];
          unset($activity_addition['EFFECTIVE_DATE']);
          // Post data to status
          $status_id = $this->record->getSelectedRecordID();
          $status_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_STATUS' => array($status_id => $activity_addition)));
          $status_poster_aff_rows = $status_poster->getResultForTable('update', 'STUD_STUDENT_STATUS')[$status_id];
          
        if ($activity_poster_aff_rows AND $status_poster_aff_rows) {
            $connect->commit();
            return $this->forward('sis_student_information_enrollment', array('record_type' => 'STUDENT_STATUS', 'record_id' => $status_id), array('record_type' => 'STUDENT_STATUS', 'record_id' => $status_id));
          } else {
            $connect->rollback();
            throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
          }
          
        } elseif ($activity_exists['EFFECTIVE_DATE'] < date('Y-m-d', strtotime($activity_post['EFFECTIVE_DATE']))) {
          // insert new record
          // Post data to activity
          $activity_addition['ENROLLMENT_ID'] = $enrollment['ENROLLMENT_ID'];
          $activity_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT_ENROLLMENT_ACTIVITY' => array('new' => $activity_addition)));
          $activity_poster_id = $activity_poster->getResultForTable('insert', 'STUD_STUDENT_ENROLLMENT_ACTIVITY')['new'];
          unset($activity_addition['EFFECTIVE_DATE'], $activity_addition['ENROLLMENT_ID']);
          // Post data to status
          $status_id = $this->record->getSelectedRecordID();
          $status_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_STATUS' => array($status_id => $activity_addition)));
          $status_poster_aff_rows = $status_poster->getResultForTable('update', 'STUD_STUDENT_STATUS')[$status_id];
          
          if ($activity_poster_id AND $status_poster_aff_rows) {
              $connect->commit();
              return $this->forward('sis_student_information_enrollment', array('record_type' => 'STUDENT_STATUS', 'record_id' => $status_id), array('record_type' => 'STUDENT_STATUS', 'record_id' => $status_id));
            } else {
              $connect->rollback();
              throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
            }
        } else {
          throw new \Kula\Component\Database\PosterFormException('Changes not saved.  Effective date cannot be before enter date ('.$enrollment['ENTER_DATE'].').');
        }
        
      }
      
      $status = $this->db()->db_select('STUD_STUDENT_STATUS')
        ->fields('STUD_STUDENT_STATUS')
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      
      $today = date('Y-m-d');
      
      if ($status['ENTER_DATE'] > $today)
        $effective_date = $status['ENTER_DATE'];
      else
        $effective_date = $today;
    
    } // end if selected record
        
    return $this->render('KulaHEdStudentBundle:SISInformation:enrollment.html.twig', array('status' => $status, 'effective_date' => $effective_date));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setSubmitMode($this->tpl, 'search');
    
    $constituents = array();
    
    
    if (isset($this->request->request->get('add')['STUD_STUDENT']['new']['STUDENT_ID'])) {
      $student_id = $this->_create_student($this->request->request->get('add')['STUD_STUDENT']['new']['STUDENT_ID']);
      return $this->forward('sis_student_information_basic', array('record_type' => 'STUDENT', 'record_id' => $student_id), array('record_type' => 'STUDENT', 'record_id' => $student_id));
    }
    
    
    if ($this->request->request->get('search')) {
      $query = \Kula\Component\Database\Searcher::prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
      $query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'BIRTH_DATE', 'GENDER'));
      $query = $query->expressions(array("AES_DECRYPT(SOCIAL_SECURITY_NUMBER, '".$GLOBALS['ssn_key']."')" => 'SOCIAL_SECURITY_NUMBER'));
      $query = $query->leftJoin('STUD_STUDENT', 'stu', null, 'stu.STUDENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
      $query = $query->leftJoin('STUD_STUDENT_STATUS', 'status', array('ORGANIZATION_TERM_ID', 'STUDENT_STATUS_ID'), 'stu.STUDENT_ID = status.STUDENT_ID AND status.ORGANIZATION_TERM_ID IN (' . implode(', ', $this->focus->getOrganizationTermIDs()) . ')');
      $query = $query->orderBy('LAST_NAME', 'ASC');
      $query = $query->orderBy('FIRST_NAME', 'ASC');
      $query = $query->range(0, 100);
      $constituents = $query->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:SISInformation:add.html.twig', array('constituents' => $constituents));
  }
  
  public function add_constituentAction() {
    $this->authorize();
    $this->formAction('sis_student_information_create_constituent');
    return $this->render('KulaHEdStudentBundle:SISInformation:add_constituent.html.twig');
  }
  
  public function create_constituentAction() {
    $this->authorize();
    
    $constituent_id = $this->_create_student();
    
    return $this->forward('sis_student_information_basic', array('record_type' => 'STUDENT', 'record_id' => $constituent_id), array('record_type' => 'STUDENT', 'record_id' => $constituent_id));
  }
  
  private function _create_student($constituent_id = false) {
    
    $connect = \Kula\Component\Database\DB::connect('write');
    
    if (!$connect->inTransaction())
      $connect->beginTransaction();
    
    if (!$constituent_id) {
    // get constituent data
    $constituent_addition = $this->request->request->get('add')['CONS_CONSTITUENT'];
    // get next Student Number
    $student_number = \Kula\Component\Sequence\Sequence::getNextSequenceForKey('PERMANENT_NUMBER');
    $constituent_addition['new']['PERMANENT_NUMBER'] = $student_number;
    // Post data
    $constituent_poster = new \Kula\Component\Database\Poster(array('CONS_CONSTITUENT' => $constituent_addition));
    // Get new constituent ID
    $constituent_id = $constituent_poster->getResultForTable('insert', 'CONS_CONSTITUENT')['new'];
    
    } else {
      // Check if student exists
      $student_id_result = $connect->select('STUD_STUDENT')->fields(null, array('STUDENT_ID'))->condition('STUDENT_ID', $constituent_id)->execute()->fetch();
      if ($student_id_result['STUDENT_ID'])
        $student_id = $student_id_result['STUDENT_ID'];
      else
        $student_id = null;
    }
    
    // get new posted data
    $student_status_addition = $this->request->request->get('add')['STUD_STUDENT_STATUS'];    
    
    if (!isset($student_id)) {
      // get student data
      $student_addition['new']['STUDENT_ID'] = $constituent_id;
      $student_addition['new']['ORIG_ENTER_DATE'] = $student_status_addition['new']['ENTER_DATE'];
      $student_addition['new']['ORIG_ENTER_CODE'] = $student_status_addition['new']['ENTER_CODE'];
      $student_addition['new']['ORIG_ENTER_TERM'] = $this->session->get('term_id');
    
      // Post data
      $student_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT' => $student_addition));
      // Get student ID
      $student_id = $student_poster->getResultForTable('insert', 'STUD_STUDENT')['new'];
    }

    // Insert new degree information
    $degree_information = $this->request->request->get('add');
    if (isset($degree_information['STUD_STUDENT_DEGREES']) AND $degree_information['STUD_STUDENT_DEGREES']['new']['DEGREE_ID'] != '') {
      
      $degree_information = $degree_information['STUD_STUDENT_DEGREES'];
      // Add on Student ID
      $degree_information['new']['STUDENT_ID'] = $constituent_id;
      $degree_information['new']['EFFECTIVE_DATE'] = $student_status_addition['new']['ENTER_DATE'];
      // Get term end date
      if (isset($degree_information['new']['TERM_ID']['value'])) {
      $this->db()->db_select('CORE_TERM', 'term')
        ->fields('term', 'END_DATE')
        ->condition('TERM_ID', $degree_information['new']['TERM_ID']['value'])
        ->execute()->fetch();
      $degree_information['new']['EXPECTED_GRADUATION_DATE'] = $degree_information['new']['TERM_ID']['value'];
      }
      // Post data
      $student_degree_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT_DEGREES' => $degree_information));
      // Get student ID
      $student_degree_id = $student_degree_poster->getResultForTable('insert', 'STUD_STUDENT_DEGREES')['new'];
    } else {
      // Look for last degree
      $last_degree = $this->db()->db_select('STUD_STUDENT_DEGREES')
        ->fields(null, array('STUDENT_DEGREE_ID'))
        ->condition('STUDENT_ID', $student_id)
        ->orderBy('EFFECTIVE_DATE', 'DESC')
        ->execute()->fetch();
      $student_degree_id = $last_degree['STUDENT_DEGREE_ID'];
    }

    // Create Student Status Record
    $student_status_addition['new']['STUDENT_ID'] = $constituent_id;
    $student_status_addition['new']['ORGANIZATION_TERM_ID'] = $this->focus->getOrganizationTermIDs()[0];
    if (isset($student_degree_id))
      $student_status_addition['new']['SEEKING_DEGREE_1_ID'] = $student_degree_id;
    
    // Get original enter term
    $enter_term = $this->db()->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('ENTER_TERM_ID', 'ADMISSIONS_COUNSELOR_ID', 'COHORT', 'ADVISOR_ID'))
      ->condition('STUDENT_ID', $constituent_id)
      ->condition('LEVEL', $student_status_addition['new']['LEVEL'])
      ->orderBy('ENTER_DATE', 'ASC')
      ->execute()->fetch();
    if ($enter_term['ENTER_TERM_ID']) {
      $student_status_addition['new']['ENTER_TERM_ID'] = $enter_term['ENTER_TERM_ID'];
    } else {
      $student_status_addition['new']['ENTER_TERM_ID'] = $this->session->get('term_id');
    }
    
    // Get last enrollment entry, if available
    $last_status_info = $this->db()->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('ADMISSIONS_COUNSELOR_ID', 'COHORT', 'ADVISOR_ID'))
      ->condition('STUDENT_ID', $constituent_id)
      ->condition('LEVEL', $student_status_addition['new']['LEVEL'])
      ->orderBy('ENTER_DATE', 'DESC')
      ->execute()->fetch();
    if ($last_status_info) {
      $student_status_addition['new']['ADMISSIONS_COUNSELOR_ID'] = $last_status_info['ADMISSIONS_COUNSELOR_ID'];
      $student_status_addition['new']['COHORT'] = $last_status_info['COHORT'];
      $student_status_addition['new']['ADVISOR_ID'] = $last_status_info['ADVISOR_ID'];  
    }
    
    $student_status_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT_STATUS' => $student_status_addition));
    $student_status_id = $student_status_poster->getResultForTable('insert', 'STUD_STUDENT_STATUS')['new'];
    
    // Create Enrollment Record
    $student_enrollment_addition = $student_status_addition;
    unset($student_enrollment_addition['new']['STUDENT_ID'], $student_enrollment_addition['new']['ORGANIZATION_TERM_ID'], 
    $student_enrollment_addition['new']['SEEKING_DEGREE_1_ID'], $student_enrollment_addition['new']['GRADE'], $student_enrollment_addition['new']['FTE'], $student_enrollment_addition['new']['THESIS_STATUS'], $student_enrollment_addition['new']['LEVEL'], $student_enrollment_addition['new']['RESIDENT'], $student_enrollment_addition['new']['ENTER_TERM_ID'], $student_enrollment_addition['new']['ADMISSIONS_COUNSELOR_ID'], $student_enrollment_addition['new']['COHORT'], $student_enrollment_addition['new']['ADVISOR_ID']);
    $student_enrollment_addition['new']['STUDENT_STATUS_ID'] = $student_status_id;
    
    $student_enrollment_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT_ENROLLMENT' => $student_enrollment_addition));
    $student_enrollment_id = $student_enrollment_poster->getResultForTable('insert', 'STUD_STUDENT_ENROLLMENT')['new'];
    
    // Create Enrollment Activity Record
    $student_enrollment_activity_addition = $student_status_addition;
    $student_enrollment_activity_addition['new']['EFFECTIVE_DATE'] = $student_enrollment_activity_addition['new']['ENTER_DATE'];
    if (isset($student_degree_id))
      $student_enrollment_activity_addition['new']['SEEKING_DEGREE_1_ID'] = $student_degree_id;
    unset($student_enrollment_activity_addition['new']['STUDENT_ID'], 
          $student_enrollment_activity_addition['new']['ORGANIZATION_TERM_ID'], 
          $student_enrollment_activity_addition['new']['ENTER_DATE'], 
          $student_enrollment_activity_addition['new']['ENTER_CODE'],
          $student_enrollment_activity_addition['new']['ENTER_TERM_ID'],
          $student_enrollment_activity_addition['new']['ADMISSIONS_COUNSELOR_ID'],
          $student_enrollment_activity_addition['new']['COHORT'],
          $student_enrollment_activity_addition['new']['ADVISOR_ID']
        );
    $student_enrollment_activity_addition['new']['ENROLLMENT_ID'] = $student_enrollment_id;
    $student_enrollment_activity_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT_ENROLLMENT_ACTIVITY' => $student_enrollment_activity_addition));
    $student_enrollment_activity_id = $student_enrollment_activity_poster->getResultForTable('insert', 'STUD_STUDENT_ENROLLMENT_ACTIVITY')['new'];
    
    // determine tuition rate
    $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
    $constituent_billing_service->determineTuitionRate($student_status_id);
    
    $student_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\StudentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
    $student_billing_service->checkMandatoryTransactions($student_status_id);
    
    if ($student_enrollment_activity_id) {
      $connect->commit();
      return $constituent_id;
    } else {
      $connect->rollback();
      throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
    }
    
  }
  
  public function index_teacherAction() {
    $this->authorize();
    return $this->render('KulaHEdStudentBundle:SISInformation:index_teacher.html.twig');
    
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = \Kula\Bundle\HEd\StudentBundle\Chooser\StudentChooser::createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}