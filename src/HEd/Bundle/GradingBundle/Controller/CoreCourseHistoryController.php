<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreCourseHistoryController extends Controller {
  
  public function coursehistoryAction() {
    $this->authorize();
    //$this->processForm();
    $this->setRecordType('Core.HEd.Student');

    $non = $this->request->request->get('non');
    
    // Add new course history records
    if ($this->request->request->get('add')) {
      $course_history_service = $this->get('kula.HEd.grading.coursehistory');
      $new_ch = $this->request->request->get('add')['HEd.Student.CourseHistory'];
      unset($new_ch['new_num']);
      foreach($new_ch as $ch_id => $ch_data) {
        $new_ch = $ch_data;
        if (isset($non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.OrganizationID']['value']))
          $new_ch[$ch_id]['HEd.Student.CourseHistory.OrganizationID'] = $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.OrganizationID']['value'];
        elseif (isset($non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.NonOrganizationID']['value']))
          $new_ch[$ch_id]['HEd.Student.CourseHistory.NonOrganizationID'] = $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.NonOrganizationID']['value'];
        
        $student_course_history_id = $course_history_service->insertCourseHistoryForCH($new_ch);
      }
    }
    
    // Edit course history records
    if ($this->request->request->get('edit')) {
      $course_history_service = $this->get('kula.HEd.grading.coursehistory');
      $edit_ch = $this->request->request->get('edit')['HEd.Student.CourseHistory'];
      foreach($edit_ch as $edit_id => $edit_data) {
        $edit_ch = $edit_data;
        if (isset($non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.OrganizationID']['value']))
          $edit_ch[$edit_id]['HEd.Student.CourseHistory.OrganizationID'] = $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.OrganizationID']['value'];
        elseif (isset($non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.NonOrganizationID']['value']))
          $edit_ch[$edit_id]['HEd.Student.CourseHistory.NonOrganizationID'] = $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.NonOrganizationID']['value'];
        $student_course_history_id = $course_history_service->updateCourseHistoryForCH($edit_id, $edit_ch);
      }
    }
    
    // Delete course history records
    if ($delete = $this->request->request->get('delete')['HEd.Student.CourseHistory']) {
      foreach($delete as $row_id => $row) {
        $student_course_history_poster = $this->newPoster()->delete('HEd.Student.CourseHistory', $row_id)->process();
      }
    }
    
    $course_history = array();
    
    if ($this->record->getSelectedRecordID()) {
    
      $course_history = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY', 'ch')
        ->fields('ch', array('COURSE_HISTORY_ID', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE', 'LEVEL', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'MARK_SCALE_ID', 'MARK'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('ch.CALENDAR_YEAR', 'ASC')
        ->orderBy('ch.CALENDAR_MONTH', 'ASC')
        ->execute()->fetchAll();
          
    }

    return $this->render('KulaHEdGradingBundle:CoreCourseHistory:coursehistory.html.twig', array('course_history' => $course_history));
  }
  
  public function detailAction($id, $sub_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
      
    $course_history = array();
    
    $course_history = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY', 'ch')
      ->fields('ch', array('COURSE_HISTORY_ID', 'COURSE_ID', 'ORGANIZATION_ID', 'NON_ORGANIZATION_ID', 'START_DATE', 'COMPLETED_DATE', 'STAFF_ID', 'STAFF_NAME', 'MARK_SCALE_ID', 'GPA_VALUE', 'QUALITY_POINTS', 'STUDENT_CLASS_ID', 'DEGREE_REQ_GRP_ID', 'TRANSFER_CREDITS', 'COMMENTS', 'REPEAT_TAG_ID', 'CALC_CREDITS_ATTEMPTED', 'CALC_CREDITS_EARNED', 'INCLUDE_TERM_GPA', 'INCLUDE_CUM_GPA'))
      ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
      ->condition('COURSE_HISTORY_ID', $sub_id)
      ->execute()->fetch();

    return $this->render('KulaHEdGradingBundle:CoreCourseHistory:coursehistory_detail.html.twig', array('course_history' => $course_history));  
  }

  public function transcriptAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student');
    
    $transcript_data = array();
    $transcript_schedule = array();
    $transcript_degree = array();
    
    if ($this->record->getSelectedRecordID()) {
      $transcript_service = $this->get('kula.HEd.grading.transcript');
      $transcript_service->loadTranscriptForStudent($this->record->getSelectedRecordID());
      $transcript_data = $transcript_service->getTranscriptData();
      $transcript_schedule = $transcript_service->getCurrentScheduleData();
      $transcript_degree = $transcript_service->getDegreeData();
    }
    return $this->render('KulaHEdGradingBundle:CoreCourseHistory:transcript.html.twig', array('data' => $transcript_data, 'schedule' => $transcript_schedule, 'degrees' => $transcript_degree));
  }
  
}