<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class APIv1ScheduleController extends APIController {

  public function addClassAction($student_id, $section_id) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    // Get organization term from section
    $section = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'ORGANIZATION_TERM_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID'))
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ID'))
      ->condition('sec.SECTION_ID', $section_id)
      ->execute()->fetch();
    if ($section['SECTION_ID'] == '') {
      throw new NotFoundHttpException('Section does not exist.');
    }

    // Find student; if doesn't exist, create student record
    $student = $this->db()->db_select('STUD_STUDENT', 'stu')
      ->fields('stu', array('STUDENT_ID'))
      ->condition('stu.STUDENT_ID', $student_id)
      ->execute()->fetch();

    // Student does not exist, create them.
    if ($student['STUDENT_ID'] == '') {

      // Get defaults
      $defaults = $this->db()->db_select('STUD_SCHOOL', 'sch')
        ->fields('sch')
        ->condition('sch.SCHOOL_ID', $section['ORGANIZATION_ID'])
        ->execute()->fetch();

       $student_id = $this->get('kula.HEd.student')->addStudent($student_id, null,
      array(
         'HEd.Student.Status.EnterDate' => date('Y-m-d'),
         'HEd.Student.OriginalEnterCode' => $defaults['DEFAULT_ENTER_CODE'],
         'HEd.Student.OriginalEnterTerm' => $section['TERM_ID'],
      )
      );
    } else {
      $student_id = $student['STUDENT_ID'];
    }
    

    // Get student status record; if doesn't exist, create it.  Determine Organization Term based on section.
    $student_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('stustatus.ORGANIZATION_TERM_ID', $section['ORGANIZATION_TERM_ID'])
      ->execute()->fetch();

    // Get defaults
      $defaults = $this->db()->db_select('STUD_SCHOOL', 'sch')
        ->fields('sch')
        ->condition('sch.SCHOOL_ID', $section['ORGANIZATION_ID'])
        ->execute()->fetch();

    // Student status does not exist, enroll student
    if ($student_status['STUDENT_STATUS_ID'] == '') {
      $student_enrollment = $this->get('kula.HEd.student')->enrollStudent(array(
        'StudentID' => $student_id,
        'OrganizationTermID' => $section['ORGANIZATION_TERM_ID'],
        'HEd.Student.Status.Grade' => $defaults['DEFAULT_GRADE'],
        'HEd.Student.Status.Level' => $defaults['DEFAULT_LEVEL'],
        'HEd.Student.Status.EnterDate' => date('Y-m-d'),
        'HEd.Student.Status.EnterCode' => $defaults['ENTER_CODE'],
      ));

      $student_status_id = $student_enrollment['student_status'];
    }

    // Create schedule record
    return $this->get('kula.HEd.scheduling.schedule')->addClassForStudentStatus($student_status_id, $section_id, date('Y-m-d'));

  }

  public function removeClass($class_id) {

    // check for authorized access to constituent

    // Remove class record

  }

  public function getClasses($student_id) {
    // check for authorized access to constituent

    // return class list
  }

  public function getClassesForCheckout() {
    // get logged in user


    // return class list
  }

}