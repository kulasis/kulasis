<?php

namespace Kula\K12\Bundle\StudentBundle\Service;

class StudentService {
  
  public function __construct($db, $sequence, $poster) {
    $this->db = $db;
    $this->sequence = $sequence;
    $this->poster = $poster;
  }
  
  public function addStudent($studentID = null, $constituentInfo = null, $studentInfo = null) {

    if (!$studentID) {
      // get next Student Number
      $constituentInfo['Core.Constituent.PermanentNumber'] = $this->sequence->getNextSequenceForKey('PERMANENT_NUMBER');
      // Post data
      $constituentID = $this->poster->newPoster()->add('Core.Constituent', 0, $constituentInfo)->process()->getID();
      
      // Get 
      $this->poster->newPoster()->add('K12.Student', 0, array(
        'K12.Student.ID' => $constituentID,
        'K12.Student.OriginalEnterDate' => $studentInfo['K12.Student.Status.EnterDate'],
        'K12.Student.OriginalEnterCode' => $studentInfo['K12.Student.Status.EnterCode'],
        'K12.Student.OriginalEnterTerm' => isset($studentInfo['K12.Student.Status.EnterTerm']) ? $studentInfo['K12.Student.Status.EnterTerm'] : $studentInfo['EnterTerm']
      ))->process();
      
      return $constituentID;
    
    } else {
      // Check if student exists
      $student_id_result = $this->db->db_select('STUD_STUDENT')->fields('STUD_STUDENT', array('STUDENT_ID'))->condition('STUDENT_ID', $studentID)->execute()->fetch();
      if ($student_id_result['STUDENT_ID']) {
        return $student_id_result['STUDENT_ID'];
      } else {
        // Get 
        $this->poster->newPoster()->add('K12.Student', 0, array(
          'K12.Student.ID' => $studentID,
          'K12.Student.OriginalEnterDate' => $studentInfo['K12.Student.Status.EnterDate'],
          'K12.Student.OriginalEnterCode' => $studentInfo['K12.Student.Status.EnterCode'],
          'K12.Student.OriginalEnterTerm' => isset($studentInfo['K12.Student.Status.EnterTerm']) ? $studentInfo['K12.Student.Status.EnterTerm'] : $studentInfo['EnterTerm']
        ))->process();
        return $studentID;
      }
    }
    
    return null;
    
  }
  
  public function enrollStudent($statusInfo) {
    
    // Get original enter term
    $enter_term = $this->db->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('ENTER_TERM_ID', 'ADMISSIONS_COUNSELOR_ID', 'COHORT', 'ADVISOR_ID'))
      ->condition('STUDENT_ID', $statusInfo['StudentID'])
      ->condition('LEVEL', $statusInfo['K12.Student.Status.Level'])
      ->orderBy('ENTER_DATE', 'ASC')
      ->execute()->fetch();
    if ($enter_term['ENTER_TERM_ID']) {
      $statusInfo['EnterTermID'] = $enter_term['ENTER_TERM_ID'];
    }
    
    // Get last enrollment entry, if available
    $last_status_info = $this->db->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('ADMISSIONS_COUNSELOR_ID', 'COHORT', 'ADVISOR_ID'))
      ->condition('STUDENT_ID', $statusInfo['StudentID'])
      ->condition('LEVEL', $statusInfo['K12.Student.Status.Level'])
      ->orderBy('ENTER_DATE', 'DESC')
      ->execute()->fetch();
    $statusInfo['AdmissionsCounselorID'] = $last_status_info['ADMISSIONS_COUNSELOR_ID'];
    $statusInfo['Cohort'] = $last_status_info['COHORT'];
    $statusInfo['AdvisorID'] = $last_status_info['ADVISOR_ID'];  
    
    $student_status_id = $this->poster->newPoster()->add('K12.Student.Status', 'new', array(
      'K12.Student.Status.StudentID' => $statusInfo['StudentID'],
      'K12.Student.Status.OrganizationTermID' => $statusInfo['OrganizationTermID'],
      'K12.Student.Status.Grade' => $statusInfo['K12.Student.Status.Grade'],
      'K12.Student.Status.Level' => $statusInfo['K12.Student.Status.Level'],
      'K12.Student.Status.Resident' => $statusInfo['K12.Student.Status.Resident'],
      'K12.Student.Status.EnterDate' => $statusInfo['K12.Student.Status.EnterDate'],
      'K12.Student.Status.EnterCode' => $statusInfo['K12.Student.Status.EnterCode'],
      'K12.Student.Status.EnterTermID' => $statusInfo['EnterTermID'],
      'K12.Student.Status.Cohort' => $statusInfo['Cohort'],
      'K12.Student.Status.AdvisorID' => $statusInfo['AdvisorID']
    ))->process()->getID();
    
    // Create Enrollment Record
    $enrollment_id = $this->poster->newPoster()->add('K12.Student.Enrollment', 'new', array(
      'K12.Student.Enrollment.StatusID' => $student_status_id,
      'K12.Student.Enrollment.EnterDate' => $statusInfo['K12.Student.Status.EnterDate'],
      'K12.Student.Enrollment.EnterCode' => $statusInfo['K12.Student.Status.EnterCode']
    ))->process()->getID();
    
    // Create Enrollment Activity Record
    $enrollment_activity_id = $this->poster->newPoster()->add('K12.Student.Enrollment.Activity', 'new', array(
      'K12.Student.Enrollment.Activity.EnrollmentID' => $enrollment_id,
      'K12.Student.Enrollment.Activity.EffectiveDate' => date('m/d/Y'),
      'K12.Student.Enrollment.Activity.Grade' => $statusInfo['K12.Student.Status.Grade'],
      'K12.Student.Enrollment.Activity.Level' => $statusInfo['K12.Student.Status.Level'],
      'K12.Student.Enrollment.Activity.Resident' => $statusInfo['K12.Student.Status.Resident']
    ))->process()->getID();
    
    return array('student_status' => $student_status_id, 'enrollment_id' => $enrollment_id, 'enrollment_activity_id' => $enrollment_activity_id);
  }
  
}