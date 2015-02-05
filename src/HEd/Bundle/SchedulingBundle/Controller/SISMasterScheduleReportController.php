<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISMasterScheduleReportController extends ReportController {
	
	public function indexAction() {
		$this->authorize();
		$this->formAction('sis_offering_sections_reports_masterschedule_generate');
		if ($this->request->query->get('record_type') == 'SECTION' AND $this->request->query->get('record_id') != '')
			$this->setRecordType('SECTION');
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		return $this->render('KulaHEdSchedulingBundle:MasterScheduleReport:reports_masterschedule.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$pdf = new \Kula\Bundle\HEd\SchedulingBundle\Controller\MasterScheduleReport("L");
		$pdf->SetFillColor(245,245,245);
		$pdf->row_count = 0;
		
		$non = $this->request->request->get('non');
		
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
		
		// Get Data and Load
		$result = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'CAPACITY', 'MINIMUM', 'ENROLLED_TOTAL', 'WAIT_LISTED_TOTAL', 'CREDITS'))
			->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', null, 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgterm.STAFF_ID');
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$result = $result->predicate('section.ORGANIZATION_TERM_ID', $org_term_ids);
		
		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$result = $result->predicate('section.SECTION_ID', $record_id);
		
		if ($non['sort'] == 'instructor') {
			$result = $result
				->order_by('ABBREVIATED_NAME', 'ASC', 'staff');
		} elseif ($non['sort'] == 'room') {
			$result = $result
				->distinct()
				->left_join('STUD_SECTION_MEETINGS', 'meetings', null, 'meetings.SECTION_ID = section.SECTION_ID')
				->left_join('STUD_ROOM', 'rooms', null, 'rooms.ROOM_ID = meetings.ROOM_ID')
				->order_by('ROOM_NUMBER', 'ASC', 'rooms')
				->order_by('MON', 'DESC', 'meetings')
				->order_by('TUE', 'DESC', 'meetings')
				->order_by('WED', 'DESC', 'meetings')
				->order_by('THU', 'DESC', 'meetings')
				->order_by('FRI', 'DESC', 'meetings')
				->order_by('SAT', 'DESC', 'meetings')
				->order_by('SUN', 'DESC', 'meetings')
				->order_by('START_TIME', 'ASC', 'meetings')
				->order_by('END_TIME', 'ASC', 'meetings');
		} else { 
			$result = $result
				->order_by('START_DATE', 'ASC', 'term');
		}
	  $result = $result->order_by('SECTION_NUMBER', 'ASC')->execute();
		
		while ($row = $result->fetch()) {
			
			if (isset($meetings[$row['SECTION_ID']]))
				$final_row = array_merge($row, $meetings[$row['SECTION_ID']]);
			else
				$final_row = $row;
			
			$pdf->setData($final_row);
			if ($pdf->row_count == 0) {
				$pdf->StartPageGroup();
				$pdf->AddPage();
				$pdf->row_count = 1;
			}
			
			$pdf->table_row($final_row);
			$pdf->row_count++;
			$pdf->row_page_count++;
			$pdf->row_total_count++;
		}
    // Closing line
	  return $this->pdfResponse($pdf->Output('','S'));
	
	}
}