<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\ReportController;

class ClassRosterReportController extends ReportController {
	
	public function indexAction() {
		$this->authorize();
		$this->formAction('sis_offering_sections_report_classroster_generate');
		if ($this->request->query->get('record_type') == 'SECTION' AND $this->request->query->get('record_id') != '')
			$this->setRecordType('SECTION');
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		return $this->render('KulaHEdSchedulingBundle:ClassRosterReport:reports_classroster.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$pdf = new \Kula\Bundle\HEd\SchedulingBundle\Controller\ClassRosterReport("P");
		$pdf->SetFillColor(245,245,245);
		$pdf->row_count = 0;
		
		$meetings = array();
		// Get meeting data
		$meeting_result = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
			->join('STUD_SECTION_MEETINGS', 'meetings', array('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'START_TIME', 'END_TIME'), 'meetings.SECTION_ID = section.SECTION_ID')
			->left_join('STUD_ROOM', 'rooms', array('ROOM_NUMBER'), 'rooms.ROOM_ID = meetings.ROOM_ID')
			->predicate('section.STATUS', null);
		
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$meeting_result = $meeting_result->predicate('section.ORGANIZATION_TERM_ID', $org_term_ids);
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$meeting_result = $meeting_result->predicate('section.SECTION_ID', $record_id);
		$meeting_result = $meeting_result
			->order_by('SECTION_ID');
		$meeting_result = $meeting_result->execute();
		$i = 0;
		$section_id = 0;
		while ($meeting_row = $meeting_result->fetch()) {
			if ($section_id != $meeting_row['SECTION_ID']) $i = 0;
			$meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] = '';
			if ($meeting_row['MON'] == 'Y') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'M';
			if ($meeting_row['TUE'] == 'Y') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'T';
			if ($meeting_row['WED'] == 'Y') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'W';
			if ($meeting_row['THU'] == 'Y') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'R';
			if ($meeting_row['FRI'] == 'Y') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'F';
			if ($meeting_row['SAT'] == 'Y') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'S';
			if ($meeting_row['SUN'] == 'Y') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'U';
			$meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['START_TIME'] = date('g:i A', strtotime($meeting_row['START_TIME']));
			$meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['END_TIME'] = date('g:i A', strtotime($meeting_row['END_TIME']));
			$meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['ROOM'] = $meeting_row['ROOM_NUMBER'];
			$i++;
			$section_id = $meeting_row['SECTION_ID'];
		}
		
		$predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
		$predicate_or = $predicate_or->predicate('DROPPED', null)->predicate('DROPPED', 'N');
		
		// Get Data and Load
		$result = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
			->join('STUD_COURSE', 'course', array('COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', null, 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgterm.STAFF_ID')
			->join('STUD_STUDENT_CLASSES', 'class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE'), 'class.SECTION_ID = section.SECTION_ID')
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID', 'SEEKING_DEGREE_1_ID'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->left_join('CORE_LOOKUP_VALUES', 'grvalue', array('DESCRIPTION' => 'GRADE'), 'grvalue.CODE = status.GRADE AND grvalue.LOOKUP_ID = 20')
			->left_join('CORE_LOOKUP_VALUES', 'entercodevalue', array('DESCRIPTION' => 'ENTER_CODE'), 'entercodevalue.CODE = status.ENTER_CODE AND entercodevalue.LOOKUP_ID = 16')
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
			->predicate($predicate_or);
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$result = $result->predicate('section.ORGANIZATION_TERM_ID', $org_term_ids);
		
		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$result = $result->predicate('section.SECTION_ID', $record_id);
		
		$result = $result
			->order_by('START_DATE', 'ASC', 'term')
			->order_by('SECTION_NUMBER', 'ASC')
			->order_by('SECTION_ID', 'ASC')
			->order_by('LAST_NAME', 'ASC', 'stucon')
			->order_by('FIRST_NAME', 'ASC', 'stucon')
		  ->execute();
		
		$last_section_id = 0;
		
		while ($row = $result->fetch()) {
			
			if (isset($meetings[$row['SECTION_ID']]))  {
			$pdf->setData(array_merge($row, $meetings[$row['SECTION_ID']]));
			} else {
				$pdf->setData($row);	
			}
			if ($last_section_id != $row['SECTION_ID']) {
				$pdf->row_count = 1;
				$pdf->StartPageGroup();
				$pdf->AddPage();
			}
			
			// Get student concentrations
			$student_concentrations = array();
			$student_concentrations_result = $this->db()->select('STUD_STUDENT_DEGREES', 'studeg')
				->fields('studeg', array())
				->join('STUD_STUDENT_DEGREES_MINORS', 'stumin', array(), 'stumin.STUDENT_DEGREE_ID = studeg.STUDENT_DEGREE_ID')
				->join('STUD_DEGREE_MINOR', 'min', array('MINOR_NAME'), 'min.MINOR_ID = stumin.MINOR_ID')
				->predicate('studeg.STUDENT_DEGREE_ID', $row['SEEKING_DEGREE_1_ID'])
				->execute();
			while ($student_concentrations_row = $student_concentrations_result->fetch()) {
				$student_concentrations[] = $student_concentrations_row['MINOR_NAME'];
			}
			$row['concentrations'] = implode(", ", $student_concentrations);
			
			$pdf->table_row($row);
			$last_section_id = $row['SECTION_ID'];
			$pdf->row_count++;
			$pdf->row_page_count++;
			$pdf->row_total_count++;
		}
    // Closing line
	  return $this->pdfResponse($pdf->Output('','S'));
	
	}
}