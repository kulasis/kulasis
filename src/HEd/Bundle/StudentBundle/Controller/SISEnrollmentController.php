<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISEnrollmentController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('HEd.Student');
    
    $statuses = array();
    
    if ($this->record->getSelectedRecordID()) {
      // Get Statuses
      $statuses = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'GRADE', 'LEVEL', 'THESIS_STATUS', 'RESIDENT', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'FTE'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->orderBy('START_DATE', 'ASC', 'term')
        ->orderBy('ENTER_DATE', 'ASC', 'STUD_STUDENT_STATUS')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:Enrollment:statuses.html.twig', array('statuses' => $statuses));
  }
  
  public function enrollmentsAction($student_status_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('HEd.Student');
    
    $enrollments = array();
    
    if ($this->record->getSelectedRecordID()) {
      // Get Status
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'GRADE', 'LEVEL', 'THESIS_STATUS', 'RESIDENT', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'FTE', 'SEEKING_DEGREE_1_ID', 'SEEKING_DEGREE_2_ID', 'ENTER_TERM_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->condition('STUDENT_STATUS_ID', $student_status_id)
        ->orderBy('term.START_DATE', 'ASC')
        ->orderBy('stustatus.ENTER_DATE', 'ASC')
        ->execute()->fetch();
      
      // Get Enrollments
      $enrollments = $this->db()->db_select('STUD_STUDENT_ENROLLMENT', 'STUD_STUDENT_ENROLLMENT')
        ->fields('STUD_STUDENT_ENROLLMENT', array('ENROLLMENT_ID', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'CREATED_TIMESTAMP', 'UPDATED_TIMESTAMP'))
        ->join('STUD_STUDENT_STATUS', 'status', 'STUD_STUDENT_ENROLLMENT.STUDENT_STATUS_ID = status.STUDENT_STATUS_ID')
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'status.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->condition('STUD_STUDENT_ENROLLMENT.STUDENT_STATUS_ID', $student_status_id)
        ->orderBy('term.START_DATE', 'ASC')
        ->orderBy('STUD_STUDENT_ENROLLMENT.ENTER_DATE', 'ASC')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:Enrollment:enrollments.html.twig', array('enrollments' => $enrollments, 'status' => $status));
  }
  
  public function activityAction($student_enrollment_id) {
    $this->authorize();
    $this->setRecordType('HEd.Student');
    
    $activity = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      // Get Enrollment
      $enrollment = $this->db()->db_select('STUD_STUDENT_ENROLLMENT', 'STUD_STUDENT_ENROLLMENT')
        ->fields('STUD_STUDENT_ENROLLMENT', array('STUDENT_STATUS_ID', 'ENROLLMENT_ID', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'CREATED_TIMESTAMP', 'UPDATED_TIMESTAMP'))
        ->join('STUD_STUDENT_STATUS', 'status', 'STUD_STUDENT_ENROLLMENT.STUDENT_STATUS_ID = status.STUDENT_STATUS_ID')
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'status.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->left_join('CONS_CONSTITUENT', 'created_user', array('LAST_NAME' => 'createduser_LAST_NAME', 'FIRST_NAME' => 'createduser_FIRST_NAME'), 'created_user.CONSTITUENT_ID = STUD_STUDENT_ENROLLMENT.CREATED_USERSTAMP')
        ->left_join('CONS_CONSTITUENT', 'updated_user', array('LAST_NAME' => 'updateduser_LAST_NAME', 'FIRST_NAME' => 'updateduser_FIRST_NAME'), 'updated_user.CONSTITUENT_ID = STUD_STUDENT_ENROLLMENT.UPDATED_USERSTAMP')
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->condition('STUD_STUDENT_ENROLLMENT.ENROLLMENT_ID', $student_enrollment_id)
        ->orderBy('term.START_DATE', 'ASC')
        ->orderBy('STUD_STUDENT_ENROLLMENT.ENTER_DATE', 'ASC')
        ->execute()->fetch();

      // Get Status
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'STUD_STUDENT_STATUS')
        ->fields('STUD_STUDENT_STATUS', array('STUDENT_STATUS_ID', 'GRADE', 'RESIDENT', 'LEVEL', 'THESIS_STATUS', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'FTE'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->condition('STUDENT_STATUS_ID', $enrollment['STUDENT_STATUS_ID'])
        ->orderBy('term.START_DATE', 'ASC')
        ->orderBy('STUD_STUDENT_STATUS.ENTER_DATE', 'ASC')
        ->execute()->fetch();
      
      // Get Activity
      $activity = $this->db()->db_select('STUD_STUDENT_ENROLLMENT_ACTIVITY', 'STUD_STUDENT_ENROLLMENT_ACTIVITY')
        ->fields('STUD_STUDENT_ENROLLMENT_ACTIVITY', array('ENROLLMENT_ACTIVITY_ID', 'ENROLLMENT_ID', 'EFFECTIVE_DATE', 'GRADE', 'LEVEL', 'THESIS_STATUS', 'RESIDENT', 'FTE', 'SEEKING_DEGREE_1_ID', 'SEEKING_DEGREE_2_ID'))
        ->join('STUD_STUDENT_ENROLLMENT', 'enrollment', 'enrollment.ENROLLMENT_ID = STUD_STUDENT_ENROLLMENT_ACTIVITY.ENROLLMENT_ID')
        ->join('STUD_STUDENT_STATUS', 'status', 'enrollment.STUDENT_STATUS_ID = status.STUDENT_STATUS_ID')
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'status.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->condition('STUD_STUDENT_ENROLLMENT_ACTIVITY.ENROLLMENT_ID', $student_enrollment_id)
        ->orderBy('term.START_DATE', 'ASC')
        ->orderBy('enrollment.ENTER_DATE', 'ASC')
        ->orderBy('STUD_STUDENT_ENROLLMENT_ACTIVITY.EFFECTIVE_DATE', 'ASC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdStudentBundle:Enrollment:activity.html.twig', array('activity' => $activity, 'enrollment' => $enrollment, 'status' => $status));
  }
  
  public function activateinactivateAction() {
    $this->authorize();
    $this->setRecordType('HEd.Student.Status');
    
    if ($this->record->getSelectedRecordID()) {
      
      // Get selected status information
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'STUDENT_ID', 'STATUS', 'GRADE', 'RESIDENT', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'FTE'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'STUD_STUDENT_STATUS.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
        ->orderBy('term.START_DATE', 'ASC')
        ->orderBy('STUD_STUDENT_STATUS.ENTER_DATE', 'ASC')
        ->execute()->fetch();
      
      if ($status['STATUS'] == '') {
        
        if (isset($this->request->request->get('edit')['STUD_STUDENT_ENROLLMENT'])) {

          $connect = \Kula\Component\Database\DB::connect('write');
          if (!$connect->inTransaction())
            $connect->beginTransaction();
          
          // update enrollment record
          $enrollment_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_ENROLLMENT' => $this->request->request->get('edit')['STUD_STUDENT_ENROLLMENT']));
          // update status record
          $status_data[$status['STUDENT_STATUS_ID']]['LEAVE_DATE'] = 
            reset($this->request->request->get('edit')['STUD_STUDENT_ENROLLMENT'])['LEAVE_DATE'];
          $status_data[$status['STUDENT_STATUS_ID']]['LEAVE_CODE'] = 
            reset($this->request->request->get('edit')['STUD_STUDENT_ENROLLMENT'])['LEAVE_CODE'];
          $status_data[$status['STUDENT_STATUS_ID']]['STATUS'] = 'I';
          $status_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_STATUS' => $status_data));
          
          // Drop all classes
          $schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
          $schedule_service->dropAllClassesForStudentStatus($status['STUDENT_STATUS_ID'], $status_data[$status['STUDENT_STATUS_ID']]['LEAVE_DATE']);
          
          // Process billing
          $student_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\StudentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
          $student_billing_service->processBilling($status['STUDENT_STATUS_ID'], 'Student Inactivated and Classes Dropped');
          
          // redirect
          if ($enrollment_poster AND $status_poster) {
            $connect->commit();
            return $this->forward('sis_student_enrollment_statuses', array('record_type' => 'STUDENT', 'record_id' => $status['STUDENT_ID']), array('record_type' => 'STUDENT', 'record_id' => $status['STUDENT_ID']));
          } else {
            $connect->rollback();
            throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
          }
          
        }
        
        // Need to get enrollment id
        $enrollment = $this->db()->db_select('STUD_STUDENT_ENROLLMENT')
          ->fields(null, array('ENROLLMENT_ID', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE'))
          ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
          ->condition('LEAVE_DATE', null)
          ->execute()->fetch();
        
        // Want to inactivate student
        return $this->render('KulaHEdStudentBundle:Enrollment:inactivate.html.twig', array('status' => $status, 'enrollment' => $enrollment));
        
      } elseif ($status['STATUS'] == 'I') {
        
        if (isset($this->request->request->get('add')['STUD_STUDENT_STATUS'])) {
          $submitted_info = $this->request->request->get('add')['STUD_STUDENT_STATUS']['new'];
          
          $connect = \Kula\Component\Database\DB::connect('write');
          if (!$connect->inTransaction())
            $connect->beginTransaction();
          
          // Update status info
          $status_data[$status['STUDENT_STATUS_ID']]['ENTER_DATE'] = $submitted_info['ENTER_DATE'];
          $status_data[$status['STUDENT_STATUS_ID']]['ENTER_CODE'] = $submitted_info['ENTER_CODE'];
          $status_data[$status['STUDENT_STATUS_ID']]['LEAVE_DATE'] = null;
          $status_data[$status['STUDENT_STATUS_ID']]['LEAVE_CODE'] = null;
          if (isset($submitted_info['GRADE'])) $status_data[$status['STUDENT_STATUS_ID']]['GRADE'] = $submitted_info['GRADE'];
          if (isset($submitted_info['RESIDENT'])) $status_data[$status['STUDENT_STATUS_ID']]['RESIDENT'] = $submitted_info['RESIDENT'];
          if (isset($submitted_info['FTE'])) $status_data[$status['STUDENT_STATUS_ID']]['FTE'] = $submitted_info['FTE'];
          $status_data[$status['STUDENT_STATUS_ID']]['STATUS'] = null;
          $status_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_STATUS' => $status_data));
          $status_poster_affected = $status_poster->getResultForTable('update', 'STUD_STUDENT_STATUS')[$status['STUDENT_STATUS_ID']];
          
          // insert enrollment record
          $enrollment_data['STUDENT_STATUS_ID'] = $status['STUDENT_STATUS_ID'];
          $enrollment_data['ENTER_DATE'] = $submitted_info['ENTER_DATE'];
          $enrollment_data['ENTER_CODE'] = $submitted_info['ENTER_CODE'];
          $enrollment_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT_ENROLLMENT' => array('new' => $enrollment_data)));
          $student_enrollment_id = $enrollment_poster->getResultForTable('insert', 'STUD_STUDENT_ENROLLMENT')['new'];
          
          // insert enrollment activity record
          $activity_data['ENROLLMENT_ID'] = $student_enrollment_id;
          if (isset($submitted_info['GRADE'])) $activity_data['GRADE'] = $submitted_info['GRADE'];
          if (isset($submitted_info['RESIDENT'])) $activity_data['RESIDENT'] = $submitted_info['RESIDENT'];
          if (isset($submitted_info['FTE'])) $activity_data['FTE'] = $submitted_info['FTE'];
          if (isset($submitted_info['LEVEL'])) $activity_data['LEVEL'] = $submitted_info['LEVEL'];
          $activity_data['EFFECTIVE_DATE'] = $submitted_info['ENTER_DATE'];
          $activity_poster = new \Kula\Component\Database\Poster(array('STUD_STUDENT_ENROLLMENT_ACTIVITY' => array('new' => $activity_data)));
          $student_activity_id = $activity_poster->getResultForTable('insert', 'STUD_STUDENT_ENROLLMENT_ACTIVITY')['new'];
          
          // determine tuition rate
          $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
          $constituent_billing_service->determineTuitionRate($this->record->getSelectedRecordID());
          
          // mandatory transactions
          $constituent_billing_service->checkMandatoryTransactions($this->record->getSelectedRecordID());
          
          if ($status_poster_affected AND $student_enrollment_id AND $student_activity_id) {
            $connect->commit();
            return $this->forward('sis_student_enrollment_statuses', array('record_type' => 'STUDENT', 'record_id' => $status['STUDENT_ID']), array('record_type' => 'STUDENT', 'record_id' => $status['STUDENT_ID']));
          } else {
            $connect->rollback();
            throw new \Kula\Component\Database\PosterFormException('Changes not saved.');
          }
          
        }
        
        // want to activate student
        return $this->render('KulaHEdStudentBundle:Enrollment:activate.html.twig', array('status' => $status));
      }
      
    } 
  }
  
}

