<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kula\Core\Bundle\FrameworkBundle\Exception\DisplayException;

class APIv1GradesController extends APIController {

  public function getStudentGradesAction($student_id, $org, $term) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $data = array();

    // Determine if grades released
    $grades_released = $this->db()->db_select('STUD_SCHOOL_TERM', 'schoolterm')
      ->fields('schoolterm', array('STUDENT_GRADES_RELEASE'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.ORGANIZATION_TERM_ID = schoolterm.SCHOOL_TERM_ID')
      ->fields('stustatus', array('STUDENT_STATUS_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->condition('stustatus.STUDENT_ID', $student_id)
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->execute()->fetch();

    if ($grades_released['STUDENT_GRADES_RELEASE'] != '' AND strtotime($grades_released['STUDENT_GRADES_RELEASE']) < time()) {

      $classes = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
        ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'DROPPED', 'DROP_DATE'))
        ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
        ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
        ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
        ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
        ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
        ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
        ->fields('staff', array('ABBREVIATED_NAME'))
        ->join('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', 'coursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
        ->fields('coursehistory', array('COURSE_HISTORY_ID', 'MARK', 'COMMENTS', 'LEVEL'))
        ->join('STUD_MARK_SCALE_MARKS', 'scalemarks', 'scalemarks.MARK = coursehistory.MARK AND class.MARK_SCALE_ID = scalemarks.MARK_SCALE_ID')
        ->fields('scalemarks', array('ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
        ->condition('class.STUDENT_STATUS_ID', $grades_released['STUDENT_STATUS_ID'])
        ->orderBy('DROPPED', 'ASC')
        ->orderBy('SECTION_NUMBER', 'ASC')
        ->orderBy('DROP_DATE', 'ASC')
        ->execute()->fetchAll();

      $gpa = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY_TERMS', 'stugpa')
        ->fields('stugpa')
        ->condition('stugpa.STUDENT_STATUS_ID', $grades_released['STUDENT_STATUS_ID'])
        ->orderBy('LEVEL', 'ASC')
        ->orderBy('CALENDAR_YEAR', 'ASC')
        ->orderBy('CALENDAR_MONTH', 'ASC')
        ->execute()->fetchAll();

      $data = array('classes' => $classes, 'gpa' => $gpa);
    } else {
      // Grades not released
      $data = 'Grades not released.';
    }

    return $this->JSONResponse($data);
  }

}
