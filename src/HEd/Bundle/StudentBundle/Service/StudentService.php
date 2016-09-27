<?php

namespace Kula\HEd\Bundle\StudentBundle\Service;

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
      
      // if ssn, update ssn
      if (isset($constituentInfo['Core.Constituent.SocialSecurityNumber']) AND $constituentInfo['Core.Constituent.SocialSecurityNumber'] != '') {
        $this->poster->newPoster()->edit('Core.Constituent', $constituentID, array('Core.Constituent.SocialSecurityNumber' => $constituentInfo['Core.Constituent.SocialSecurityNumber']))->process();
      }
      
      // Get 
      $this->poster->newPoster()->add('HEd.Student', 0, array(
        'HEd.Student.ID' => $constituentID,
        'HEd.Student.OriginalEnterDate' => $studentInfo['HEd.Student.Status.EnterDate'],
        'HEd.Student.OriginalEnterCode' => $studentInfo['HEd.Student.Status.EnterCode'],
        'HEd.Student.OriginalEnterTerm' => isset($studentInfo['HEd.Student.Status.EnterTerm']) ? $studentInfo['HEd.Student.Status.EnterTerm'] : $studentInfo['EnterTerm']
      ))->process();
      
      return $constituentID;
    
    } else {
      // Check if student exists
      $student_id_result = $this->db->db_select('STUD_STUDENT')->fields('STUD_STUDENT', array('STUDENT_ID'))->condition('STUDENT_ID', $studentID)->execute()->fetch();
      if ($student_id_result['STUDENT_ID']) {
        return $student_id_result['STUDENT_ID'];
      } else {
        // Get 
        $this->poster->newPoster()->add('HEd.Student', 0, array(
          'HEd.Student.ID' => $studentID,
          'HEd.Student.OriginalEnterDate' => $studentInfo['HEd.Student.Status.EnterDate'],
          'HEd.Student.OriginalEnterCode' => $studentInfo['HEd.Student.Status.EnterCode'],
          'HEd.Student.OriginalEnterTerm' => isset($studentInfo['HEd.Student.Status.EnterTerm']) ? $studentInfo['HEd.Student.Status.EnterTerm'] : $studentInfo['EnterTerm']
        ))->process();
        return $studentID;
      }
    }
    
    return null;
    
  }
  
  public function addDegree($degreeInfo) {
    // Insert new degree information
    if (isset($degree_information['HEd.Student.Degree']) AND $degree_information['HEd.Student.Degree']['new']['HEd.Student.Degree.ID'] != '') {
      // Get term end date
      if (isset($degreeInfo['ExpectedCompletionTermID'])) {
        $degreeTerm = $this->db->db_select('CORE_TERM', 'term')
          ->fields('term', array('END_DATE', 'TERM_ID'))
          ->condition('TERM_ID', $degreeInfo['ExpectedCompletionTermID'])
          ->execute()->fetch();
      }
      
      $student_degree_id = $this->poster->newPoster()->add('HEd.Student.Degree', 'new', array(
        'HEd.Student.Degree.StudentID' => $degreeInfo['StudentID'],
        'HEd.Student.Degree.DegreeID' => $degreeInfo['DegreeID'],
        'HEd.Student.Degree.EffectiveDate' => $degreeInfo['EffectiveDate'],
        'HEd.Student.Degree.ExpectedGraduationDate' => (isset($degreeTerm['END_DATE'])) ? $degreeTerm['END_DATE'] : null,
        'HEd.Student.Degree.ExpectedCompletionTermID' => (isset($degreeTerm['TERM_ID'])) ? $degreeTerm['TERM_ID'] : null,
      ))->process()->getID();
        
    } else {
      // Look for last degree
      $last_degree = $this->db->db_select('STUD_STUDENT_DEGREES')
        ->fields('STUD_STUDENT_DEGREES', array('STUDENT_DEGREE_ID'))
        ->condition('STUDENT_ID', $degreeInfo['StudentID'])
        ->orderBy('EFFECTIVE_DATE', 'DESC')
        ->execute()->fetch();
      $student_degree_id = $last_degree['STUDENT_DEGREE_ID'];
    }
    
    return $student_degree_id;
    
  }
  
  public function enrollStudent($statusInfo, $options = array()) {
    
    // Get original enter term
    $enter_term = $this->db->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('ENTER_TERM_ID', 'ADMISSIONS_COUNSELOR_ID', 'COHORT', 'ADVISOR_ID'))
      ->condition('STUDENT_ID', $statusInfo['StudentID'])
      ->condition('LEVEL', $statusInfo['HEd.Student.Status.Level'])
      ->orderBy('ENTER_DATE', 'ASC')
      ->execute()->fetch();
    if ($enter_term['ENTER_TERM_ID']) {
      $statusInfo['EnterTermID'] = $enter_term['ENTER_TERM_ID'];
    }
    
    // Get last enrollment entry, if available
    $last_status_info = $this->db->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('ADMISSIONS_COUNSELOR_ID', 'COHORT', 'ADVISOR_ID'))
      ->condition('STUDENT_ID', $statusInfo['StudentID'])
      ->condition('LEVEL', $statusInfo['HEd.Student.Status.Level'])
      ->orderBy('ENTER_DATE', 'DESC')
      ->execute()->fetch();
    $statusInfo['AdmissionsCounselorID'] = $last_status_info['ADMISSIONS_COUNSELOR_ID'];
    $statusInfo['Cohort'] = $last_status_info['COHORT'];
    $statusInfo['AdvisorID'] = $last_status_info['ADVISOR_ID'];  
    
    $student_status_id = $this->poster->newPoster()->add('HEd.Student.Status', 'new', array(
      'HEd.Student.Status.StudentID' => $statusInfo['StudentID'],
      'HEd.Student.Status.OrganizationTermID' => $statusInfo['OrganizationTermID'],
      'HEd.Student.Status.Grade' => $statusInfo['HEd.Student.Status.Grade'],
      'HEd.Student.Status.Level' => $statusInfo['HEd.Student.Status.Level'],
      'HEd.Student.Status.Resident' => $statusInfo['HEd.Student.Status.Resident'],
      'HEd.Student.Status.EnterDate' => $statusInfo['HEd.Student.Status.EnterDate'],
      'HEd.Student.Status.EnterCode' => $statusInfo['HEd.Student.Status.EnterCode'],
      'HEd.Student.Status.SeekingDegree1ID' => $statusInfo['SeekingDegree1ID'],
      'HEd.Student.Status.EnterTermID' => $statusInfo['EnterTermID'],
      'HEd.Student.Status.AdmissionsCounselorID' => $statusInfo['AdmissionsCounselorID'],
      'HEd.Student.Status.Cohort' => $statusInfo['Cohort'],
      'HEd.Student.Status.AdvisorID' => $statusInfo['AdvisorID'],
      'HEd.Student.Status.SeekingDegree1ID' => $statusInfo['SeekingDegree1ID']
    ))->process($options)->getID();
echo $student_status_id;
    // Create Enrollment Record
    $enrollment_id = $this->poster->newPoster()->add('HEd.Student.Enrollment', 'new', array(
      'HEd.Student.Enrollment.StatusID' => $student_status_id,
      'HEd.Student.Enrollment.EnterDate' => $statusInfo['HEd.Student.Status.EnterDate'],
      'HEd.Student.Enrollment.EnterCode' => $statusInfo['HEd.Student.Status.EnterCode']
    ))->process($options)->getID();
    
    // Create Enrollment Activity Record
    $enrollment_activity_id = $this->poster->newPoster()->add('HEd.Student.Enrollment.Activity', 'new', array(
      'HEd.Student.Enrollment.Activity.EnrollmentID' => $enrollment_id,
      'HEd.Student.Enrollment.Activity.EffectiveDate' => date('m/d/Y'),
      'HEd.Student.Enrollment.Activity.Grade' => $statusInfo['HEd.Student.Status.Grade'],
      'HEd.Student.Enrollment.Activity.Level' => $statusInfo['HEd.Student.Status.Level'],
      'HEd.Student.Enrollment.Activity.Resident' => $statusInfo['HEd.Student.Status.Resident']
    ))->process($options)->getID();
    
    return array('student_status' => $student_status_id, 'enrollment_id' => $enrollment_id, 'enrollment_activity_id' => $enrollment_activity_id);
  }
  
}