<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreEnrollmentController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $statuses = array();
    
    if ($this->record->getSelectedRecordID()) {
      // Get Statuses
      $statuses = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'GRADE', 'LEVEL', 'THESIS_STATUS', 'RESIDENT', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'FTE'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->orderBy('START_DATE', 'ASC', 'term')
        ->orderBy('ENTER_DATE', 'ASC', 'STUD_STUDENT_STATUS')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:CoreEnrollment:statuses.html.twig', array('statuses' => $statuses));
  }
  
  public function enrollmentsAction($student_status_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $enrollments = array();
    
    if ($this->record->getSelectedRecordID()) {
      // Get Status
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'GRADE', 'LEVEL', 'THESIS_STATUS', 'RESIDENT', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'LEAVE_REASON', 'FTE', 'SEEKING_DEGREE_1_ID', 'SEEKING_DEGREE_2_ID', 'ENTER_TERM_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
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
    
    return $this->render('KulaHEdStudentBundle:CoreEnrollment:enrollments.html.twig', array('enrollments' => $enrollments, 'status' => $status));
  }
  
  public function activityAction($student_enrollment_id) {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    
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
        ->leftJoin('CONS_CONSTITUENT', 'created_user', 'created_user.CONSTITUENT_ID = STUD_STUDENT_ENROLLMENT.CREATED_USERSTAMP')
        ->fields('created_user', array('LAST_NAME' => 'createduser_LAST_NAME', 'FIRST_NAME' => 'createduser_FIRST_NAME'))
        ->leftJoin('CONS_CONSTITUENT', 'updated_user', 'updated_user.CONSTITUENT_ID = STUD_STUDENT_ENROLLMENT.UPDATED_USERSTAMP')
        ->fields('updated_user', array('LAST_NAME' => 'updateduser_LAST_NAME', 'FIRST_NAME' => 'updateduser_FIRST_NAME'))
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
    
    return $this->render('KulaHEdStudentBundle:CoreEnrollment:activity.html.twig', array('activity' => $activity, 'enrollment' => $enrollment, 'status' => $status));
  }
  
  public function activateinactivateAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    
    if ($this->record->getSelectedRecordID()) {
      
      // Get selected status information
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'STUDENT_ID', 'STATUS', 'GRADE', 'RESIDENT', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE', 'FTE'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION', 'START_DATE'))
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
        ->orderBy('term.START_DATE', 'ASC')
        ->orderBy('stustatus.ENTER_DATE', 'ASC')
        ->execute()->fetch();
      
      if ($status['STATUS'] == '') {
        
        if ($enrollmentInfo = $this->form('edit', 'HEd.Student.Enrollment')) {

          $transaction = $this->db()->db_transaction();
          // update enrollment record
          $enrollmentPoster = $this->newPoster()->edit('HEd.Student.Enrollment', key($enrollmentInfo), current($enrollmentInfo))->process()->getResult();
          
          // update status record
          $statusPoster = $this->newPoster()->edit('HEd.Student.Status', $status['STUDENT_STATUS_ID'], array(
            'HEd.Student.Status.LeaveDate' => $enrollmentInfo[key($enrollmentInfo)]['HEd.Student.Enrollment.LeaveDate'],
            'HEd.Student.Status.LeaveCode' => $enrollmentInfo[key($enrollmentInfo)]['HEd.Student.Enrollment.LeaveCode'],
            'HEd.Student.Status.Status' => 'I'
          ))->process()->getResult();
          
          // Drop all classes
          $schedule_service = $this->get('kula.HEd.scheduling.schedule');
          $schedule_service->dropAllClassesForStudentStatus($status['STUDENT_STATUS_ID'], $enrollmentInfo[key($enrollmentInfo)]['HEd.Student.Enrollment.LeaveDate']);
          
          // Process billing
          $student_billing_service = $this->get('kula.HEd.billing.student');
          $student_billing_service->processBilling($status['STUDENT_STATUS_ID'], 'Student Inactivated and Classes Dropped');
          
          // redirect
          if ($enrollmentPoster AND $statusPoster) {
            $transaction->commit();
            $this->addFlash('success', 'Inactivated student.');
            return $this->forward('core_HEd_student_enrollment_statuses', array('record_type' => 'Core.HEd.Student', 'record_id' => $status['STUDENT_ID']), array('record_type' => 'Core.HEd.Student', 'record_id' => $status['STUDENT_ID']));
          } else {
            $transaction->rollback();
            throw new \Kula\Core\Component\DB\PosterException('Inactivation failed.');
          }
          
        }
        
        // Need to get enrollment
        $enrollment = $this->db()->db_select('STUD_STUDENT_ENROLLMENT')
          ->fields('STUD_STUDENT_ENROLLMENT', array('ENROLLMENT_ID', 'ENTER_DATE', 'ENTER_CODE', 'LEAVE_DATE', 'LEAVE_CODE'))
          ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
          ->condition('LEAVE_DATE', null)
          ->execute()->fetch();
        
        // Want to inactivate student
        return $this->render('KulaHEdStudentBundle:CoreEnrollment:inactivate.html.twig', array('status' => $status, 'enrollment' => $enrollment));
        
      } elseif ($status['STATUS'] == 'I') {
        
        if ($enrollmentInfo = $this->form('add', 'HEd.Student.Status', 'new')) {
          
          $transaction = $this->db()->db_transaction();
          
          // Update status info
          $enrollmentInfo['HEd.Student.Status.Status'] = null;
          $statusPoster = $this->newPoster()->edit('HEd.Student.Status', $status['STUDENT_STATUS_ID'], $enrollmentInfo)->process()->getResult();
          
          // insert enrollment record
          $enrollmentPoster = $this->newPoster()->add('HEd.Student.Enrollment', 'new', array(
            'HEd.Student.Enrollment.StatusID' => $status['STUDENT_STATUS_ID'],
            'HEd.Student.Enrollment.EnterDate' => $enrollmentInfo['HEd.Student.Status.EnterDate'],
            'HEd.Student.Enrollment.EnterCode' => $enrollmentInfo['HEd.Student.Status.EnterCode']
          ))->process()->getResult();

          // insert enrollment activity record
          $activityPoster = $this->newPoster()->add('HEd.Student.Enrollment.Activity', 'new', array(
            'HEd.Student.Enrollment.Activity.EnrollmentID' => $enrollmentPoster,
            'HEd.Student.Enrollment.Activity.Grade' => (isset($enrollmentInfo['HEd.Student.Status.Grade'])) ? $enrollmentInfo['HEd.Student.Status.Grade'] : null,
            'HEd.Student.Enrollment.Activity.Resident' => (isset($enrollmentInfo['HEd.Student.Status.Resident'])) ? $enrollmentInfo['HEd.Student.Status.Resident'] : null,
            'HEd.Student.Enrollment.Activity.FTE' => (isset($enrollmentInfo['HEd.Student.Status.FTE'])) ? $enrollmentInfo['HEd.Student.Status.FTE'] : null,
            'HEd.Student.Enrollment.Activity.Level' => (isset($enrollmentInfo['HEd.Student.Status.Level'])) ? $enrollmentInfo['HEd.Student.Status.Level'] : null,
            'HEd.Student.Enrollment.Activity.EffectiveDate' => date('m/d/Y')
          ))->process()->getResult();
          
          // determine tuition rate
          $constituent_billing_service = $this->get('kula.HEd.billing.constituent');
          $constituent_billing_service->determineTuitionRate($this->record->getSelectedRecordID());
          
          // mandatory transactions
          $student_billing_service = $this->get('kula.HEd.billing.student');
          $student_billing_service->checkMandatoryTransactions($this->record->getSelectedRecordID());
          
          if ($statusPoster AND $enrollmentPoster AND $activityPoster) {
            $transaction->commit();
            $this->addFlash('success', 'Activated student.');
            return $this->forward('core_HEd_student_enrollment_statuses', array('record_type' => 'Core.HEd.Student', 'record_id' => $status['STUDENT_ID']), array('record_type' => 'Core.HEd.Student', 'record_id' => $status['STUDENT_ID']));
          } else {
            $transaction->rollback();
            throw new \Kula\Core\Component\DB\PosterException('Activation failed.');
          }
          
        }
        
        // Get enter date to use
        $sch_info = $this->db()->db_select('STUD_SCHOOL', 'sch')
          ->fields('sch', array('DEFAULT_ENTER_DATE_ACTION'))
          ->condition('sch.SCHOOL_ID', $this->focus->getOrganizationID())
          ->execute()->fetch();

        if ($sch_info['DEFAULT_ENTER_DATE_ACTION'] == 'TERM' AND $this->focus->getTermStartDate() > date('Y-m-d')) {
          $default_enter_date = $this->focus->getTermStartDate();
        } else {
          $default_enter_date = date('m/d/Y');
        }

        // want to activate student
        return $this->render('KulaHEdStudentBundle:CoreEnrollment:activate.html.twig', array('status' => $status, 'default_enter_date' => $default_enter_date));
      }
      
    } 
  }
  
  public function editAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    
    $status = array();
    $effective_date = null;
    
    if ($this->record->getSelectedRecordID()) {
      
      // Add enrollment activity
      if ($activity_post = $this->form('add', 'HEd.Student.Enrollment.Activity', 'new')) {
        
        // Get current values
        $current_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
          ->fields('stustatus', array('GRADE', 'RESIDENT', 'FTE', 'LEVEL', 'THESIS_STATUS', 'SEEKING_DEGREE_1_ID', 'SEEKING_DEGREE_2_ID'))
          ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
          ->execute()->fetch();
        
        // posted data
        $transaction = $this->db()->db_transaction();
        
        if ($activity_post['HEd.Student.Enrollment.Activity.Grade']) {
          $activity_data['HEd.Student.Enrollment.Activity.Grade'] = $activity_post['HEd.Student.Enrollment.Activity.Grade'];
        } else {
          $activity_data['HEd.Student.Enrollment.Activity.Grade'] = $current_status['GRADE'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Resident']) {
          if ($activity_post['HEd.Student.Enrollment.Activity.Resident'] == '(blank)') {
            $activity_data['HEd.Student.Enrollment.Activity.Resident'] = null;
          } else {
            $activity_data['HEd.Student.Enrollment.Activity.Resident'] = $activity_post['HEd.Student.Enrollment.Activity.Resident'];
          }
        } else {
          $activity_data['HEd.Student.Enrollment.Activity.Resident'] = $current_status['RESIDENT'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.FTE']) {
          if ($activity_post['HEd.Student.Enrollment.Activity.FTE'] == '(blank)') {
            $activity_data['HEd.Student.Enrollment.Activity.FTE'] = null;
          } else {
            $activity_data['HEd.Student.Enrollment.Activity.FTE'] = $activity_post['HEd.Student.Enrollment.Activity.FTE'];
          }
        } else {
           $activity_data['HEd.Student.Enrollment.Activity.FTE'] = $current_status['FTE'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Level']) {
          $activity_data['HEd.Student.Enrollment.Activity.Level'] = $activity_post['HEd.Student.Enrollment.Activity.Level'];
        } else {
          $activity_data['HEd.Student.Enrollment.Activity.Level'] = $current_status['LEVEL'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.ThesisStatus']) {
          if ($activity_post['HEd.Student.Enrollment.Activity.ThesisStatus'] == '(blank)') {
            $activity_data['HEd.Student.Enrollment.Activity.ThesisStatus'] = null;
          } else {
            $activity_data['HEd.Student.Enrollment.Activity.ThesisStatus'] = $activity_post['HEd.Student.Enrollment.Activity.ThesisStatus'];
          }
        } else {
          $activity_data['HEd.Student.Enrollment.Activity.ThesisStatus'] = $current_status['THESIS_STATUS'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.SeekingDegree1ID']) {
          $activity_data['HEd.Student.Enrollment.Activity.SeekingDegree1ID'] = $activity_post['HEd.Student.Enrollment.Activity.SeekingDegree1ID'];
        } else {
          $activity_data['HEd.Student.Enrollment.Activity.SeekingDegree1ID'] = $current_status['SEEKING_DEGREE_1_ID'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.SeekingDegree2ID']) {
          $activity_data['HEd.Student.Enrollment.Activity.SeekingDegree2ID'] = $activity_post['HEd.Student.Enrollment.Activity.SeekingDegree2ID'];
        } else {
          $activity_data['HEd.Student.Enrollment.Activity.SeekingDegree2ID'] = $current_status['SEEKING_DEGREE_2_ID'];
        }
        
        // Post data to status
        if ($activity_post['HEd.Student.Enrollment.Activity.Grade']) {
          $status_data['HEd.Student.Status.Grade'] = $activity_post['HEd.Student.Enrollment.Activity.Grade'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Resident']) {
          if ($activity_post['HEd.Student.Enrollment.Activity.Resident'] == '(blank)') {
            $status_data['HEd.Student.Status.Resident'] = null;
          } else {
            $status_data['HEd.Student.Status.Resident'] = $activity_post['HEd.Student.Enrollment.Activity.Resident'];
          }
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.FTE']) {
          if ($activity_post['HEd.Student.Status.FTE'] == '(blank)') {
            $status_data['HEd.Student.Status.FTE'] = null;
          } else {
            $status_data['HEd.Student.Status.FTE'] = $activity_post['HEd.Student.Enrollment.Activity.FTE'];
          }
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.Level']) {
          $status_data['HEd.Student.Status.Level'] = $activity_post['HEd.Student.Enrollment.Activity.Level'];
        }
        if ($activity_post['HEd.Student.Enrollment.Activity.ThesisStatus']) {
          if ($activity_post['HEd.Student.Enrollment.Activity.ThesisStatus'] == '(blank)') {
            $status_data['HEd.Student.Status.ThesisStatus'] = null;
          } else {
            $status_data['HEd.Student.Status.ThesisStatus'] = $activity_post['HEd.Student.Enrollment.Activity.ThesisStatus'];
          }
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
          return $this->forward('core_HEd_student_enrollment_statuses', array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
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
        
    return $this->render('KulaHEdStudentBundle:CoreEnrollment:edit.html.twig', array('status' => $status, 'effective_date' => $effective_date));
  }

  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.HEd.StudentStatus')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
  public function combineAction() {
    $this->authorize();
    
    $combine = $this->request->request->get('non');
    if (isset($combine['HEd.Student.Status']['delete']['HEd.Student.Status.ID']['value']) AND isset($combine['HEd.Student.Status']['keep']['HEd.Student.Status.ID']['value'])) {
      
      if ($result = $this->get('kula.Core.Combine')->combine('STUD_STUDENT_STATUS', $combine['HEd.Student.Status']['delete']['HEd.Student.Status.ID']['value'], $combine['HEd.Student.Status']['keep']['HEd.Student.Status.ID']['value'])) {
        $this->addFlash('success', 'Combined student statuses.');
      } else {
        $this->addFlash('error', 'Unable to combined student statuses.');
      }
      
    }

    return $this->render('KulaHEdStudentBundle:CoreEnrollment:combine.html.twig');
  }
  
}

