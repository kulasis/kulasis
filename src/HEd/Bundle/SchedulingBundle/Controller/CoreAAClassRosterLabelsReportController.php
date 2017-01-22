<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreAAClassRosterLabelsReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    if ($this->request->query->get('record_type') == 'Core.HEd.Section' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Section');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    return $this->render('KulaHEdSchedulingBundle:CoreClassRosterReport:reports_classroster_labels.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
	/*------------------------------------------------
	To create the object, 2 possibilities:
	either pass a custom format via an array
	or use a built-in AVERY name
	------------------------------------------------*/
	
	// Example of custom format
	// $pdf = new PDF_Label(array('paper-size'=>'A4', 'metric'=>'mm', 'marginLeft'=>1, 'marginTop'=>1, 'NX'=>2, 'NY'=>7, 'SpaceX'=>0, 'SpaceY'=>0, 'width'=>99, 'height'=>38, 'font-size'=>14));
	$form = $this->request->request->get('non');
	
	// Standard format
	if (isset($form['label']) AND $form['label'] == "5160") {
		$pdf = new \Kula\Core\Component\Label\PDF_Label(array('paper-size'=>'letter', 'metric'=>'mm',	'marginLeft'=>3,	'marginTop'=>18,		'NX'=>3,	'NY'=>10,	
		'SpaceX'=>3.58 /* 3.175 */,	'SpaceY'=>0,	'width'=>68,	'height'=>25.4,	'font-size'=>12));
	} elseif (isset($form['label'])) {
		$pdf = new \Kula\Core\Component\Label\PDF_Label($form['label']);
	} else {
		$pdf = new \Kula\Core\Component\Label\PDF_Label('L7163');
	}

	$pdf->AddPage();
	
    // Get Data and Load
    $result = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_TITLE', 'SHORT_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->join('STUD_STUDENT_CLASSES', 'class', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'AGE', 'SHIRT_SIZE', 'GROUP_WITH'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'grvalue', "grvalue.CODE = status.GRADE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
      ->fields('grvalue', array('DESCRIPTION' => 'GRADE'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'entercodevalue', "entercodevalue.CODE = status.ENTER_CODE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.EnterCode')")
      ->fields('entercodevalue', array('DESCRIPTION' => 'ENTER_CODE'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->fields('student', array('STUDENT_ID', 'MEDICAL_NOTES'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'NOTES'))
      ->condition('DROPPED', '0');
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('section.ORGANIZATION_TERM_ID', $org_term_ids);
    
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->condition('section.SECTION_ID', $record_id);
    
	  if (isset($form['section_number']) AND $form['section_number'] != '') {
		  $result = $result->condition('section.SECTION_NUMBER', $form['section_number'], 'LIKE');
	  }
	
    $result = $result
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->orderBy('SECTION_ID', 'ASC')
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->execute();
    
    $last_section_id = 0;
    
    while ($row = $result->fetch()) {
		// Print labels
		$text = sprintf("%s\n%s\n%s", 
			$row['LAST_NAME'].', '.$row['FIRST_NAME'].' '.$row['AGE'], 
			$row['PERMANENT_NUMBER'].' | '.$row['GENDER'].' |  '.$row['SECTION_NUMBER'], 
			$row['GROUP_WITH']);
		$pdf->Add_Label($text);
	}

	return $this->pdfResponse($pdf->Output('','S'));
  }
}