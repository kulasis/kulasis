<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class APIv1ScheduleController extends APIController {

  public function addClassAction($student_id, $section_id) {

    $schedule = null;

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    // Get organization term from section
    $section = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'ORGANIZATION_TERM_ID', 'OPEN_REGISTRATION', 'CLOSE_REGISTRATION', 'ALLOW_REGISTRATION', 'CAPACITY'))
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
    if ($section['ALLOW_REGISTRATION'] == 0) {
      throw new NotFoundHttpException('Section does not allow registration.');
    }
    if (date('Y-m-d') < $section['OPEN_REGISTRATION'] OR date('Y-m-d') > $section['CLOSE_REGISTRATION']) {
      throw new NotFoundHttpException('Section closed from registration.');
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

    // Make sure class not full
    $transaction = $this->database->db_transaction();

    $class_count = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->expression('COUNT(*)', 'class_total')
      ->condition('class.SECTION_ID', $section_id)
      ->condition('class.DROPPED', 0)
      ->execute()
      ->fetch();
    if ($class_count['class_total'] <= $section['CAPACITY']) {
      $schedule = $this->get('kula.HEd.scheduling.schedule')->addClassForStudentStatus($student_status_id, $section_id, date('Y-m-d'));

      $transaction->commit();
    }


    // Create schedule record
    return $this->jsonResponse($schedule);

  }

  public function removeClassAction($class_id) {

    // get student ID from class ID
    $student_id = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('stustatus', array('STUDENT_ID'))
      ->condition('class.STUDENT_CLASS_ID', $class_id)
      ->execute()->fetch()['STUDENT_ID'];

    // check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    // Remove class record
    return $this->jsonResponse($this->get('kula.HEd.scheduling.schedule')->dropClassForStudentStatus($class_id, date('Y-m-d')));

  }

  public function getClassesAction($student_id, $org = null, $term = null) {
    // check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    // return class list
    $class_list = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array())
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = classes.SECTION_ID')
      ->fields('sec', array('SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = sec.COURSE_ID')
      ->fields('course', array('COURSE_TITLE"'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('classes.DROPPED', 0)
      ->condition('classes.START_DATE', date('Y-m-d'), '>=')
      ->execute()->fetchAll();

    return $this->jsonResponse($class_list);
  }

  public function getClassesForCheckout() {
    // get logged in user


    // return class list
  }

}