<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\ReportController;

class StaffScheduleReportController extends ReportController {
	
	public function indexAction() {
		$this->authorize();
		if (($this->request->query->get('record_type') == 'STAFF' || $this->request->query->get('record_type') == 'STAFF_SCHOOL_TERM') AND $this->request->query->get('record_id') != '')
			$this->setRecordType('STAFF_SCHOOL_TERM');
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		return $this->render('KulaHEdSchedulingBundle:StaffScheduleReport:reports_staffschedule.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$pdf = new \Kula\Bundle\HEd\SchedulingBundle\Controller\StaffScheduleReport("P");
		$pdf->SetFillColor(245,245,245);
		$pdf->row_count = 0;
		
		// Get student addresses
		
		$meetings = array();
		// Get meeting data
		$meeting_result = $this->db()->select('STUD_SECTION', 'section')
			->distinct(true)
			->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
			->join('STUD_SECTION_MEETINGS', 'meetings', array('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'START_TIME', 'END_TIME'), 'meetings.SECTION_ID = section.SECTION_ID')
			->join('STUD_STUDENT_CLASSES', 'class', null, 'class.SECTION_ID = section.SECTION_ID')
			->left_join('STUD_ROOM', 'rooms', array('ROOM_NUMBER'), 'rooms.ROOM_ID = meetings.ROOM_ID');
		
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$meeting_result = $meeting_result->predicate('section.ORGANIZATION_TERM_ID', $org_term_ids);
		$record_id = $this->request->request->get('record_id');		
		if (isset($record_id) AND $record_id != '')
			$meeting_result = $meeting_result->predicate('section.STAFF_ORGANIZATION_TERM_ID', $record_id);
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
		
		// Get Data and Load
		$result = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'CREDITS', 'STAFF_ORGANIZATION_TERM_ID'))
			->join('STUD_COURSE', 'course', array('COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
			->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', null, 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
			->join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgterm.STAFF_ID')
			;
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$result = $result->predicate('stafforgterm.ORGANIZATION_TERM_ID', $org_term_ids);
		
		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$result = $result->predicate('section.STAFF_ORGANIZATION_TERM_ID', $record_id);

		$result = $result
			->order_by('ABBREVIATED_NAME', 'ASC', 'staff')
			->order_by('START_DATE', 'ASC', 'term')
			->order_by('SECTION_NUMBER', 'ASC')
			->order_by('SECTION_ID', 'ASC')
		  ->execute();
		
		$last_student_status_id = 0;
		$credit_total = 0;
		while ($row = $result->fetch()) {
			
			if (isset($meetings[$row['SECTION_ID']]))  {
				$row = array_merge($row, $meetings[$row['SECTION_ID']]);
			}
			if ($last_student_status_id != $row['STAFF_ORGANIZATION_TERM_ID']) {
				$pdf->setData($row);
				$pdf->row_count = 1;
				$credit_total = 0;
				$pdf->StartPageGroup();
				$pdf->AddPage();
			}
			
			$pdf->table_row($row);
			$credit_total += $row['CREDITS'];
			$last_student_status_id = $row['STAFF_ORGANIZATION_TERM_ID'];
			$pdf->row_count++;
			$pdf->row_page_count++;
			$pdf->row_total_count++;
		}
		
    // Closing line
	  return $this->pdfResponse($pdf->Output('','S'));
	
	}
}