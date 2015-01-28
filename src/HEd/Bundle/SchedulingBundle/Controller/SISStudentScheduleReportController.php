<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\ReportController;

class StudentScheduleReportController extends ReportController {
	
	public function indexAction() {
		$this->authorize();
		$this->formAction('sis_student_schedule_reports_studentschedule_generate');
		if ($this->request->query->get('record_type') == 'STUDENT_STATUS' AND $this->request->query->get('record_id') != '')
			$this->setRecordType('STUDENT_STATUS');
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		return $this->render('KulaHEdSchedulingBundle:StudentScheduleReport:reports_studentschedule.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$pdf = new \Kula\Bundle\HEd\SchedulingBundle\Controller\StudentScheduleReport("P");
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
			$meeting_result = $meeting_result->predicate('class.STUDENT_STATUS_ID', $record_id);
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
		
		$dropped_conditions = new \Kula\Component\Database\Query\Predicate('OR');
		$dropped_conditions = $dropped_conditions->predicate('class.DROPPED', null);
		$dropped_conditions = $dropped_conditions->predicate('class.DROPPED', 'N');
		
		// Get Data and Load
		$result = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('CREDITS_ATTEMPTED'))
			->left_join('STUD_MARK_SCALE', 'markscale', array('MARK_SCALE_NAME'), 'markscale.MARK_SCALE_ID = class.MARK_SCALE_ID')
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->left_join('CORE_LOOKUP_VALUES', 'grvalue', array('DESCRIPTION' => 'GRADE'), 'grvalue.CODE = status.GRADE AND grvalue.LOOKUP_ID = 20')
			->left_join('STUD_STUDENT_DEGREES', 'studdegrees', null, 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
			->left_join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'advisororgterm', null, 'advisororgterm.STAFF_ORGANIZATION_TERM_ID = status.ADVISOR_ID')
			->left_join('STUD_STAFF', 'advisor', array('ABBREVIATED_NAME' => 'advisor_ABBREVIATED_NAME'), 'advisor.STAFF_ID = advisororgterm.STAFF_ID')
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
			->left_join('CONS_ADDRESS', 'res_address', array('ADDRESS' => 'res_ADDRESS', 'CITY' => 'res_CITY', 'STATE' => 'res_STATE', 'ZIPCODE' => 'res_ZIPCODE'), 'res_address.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID')
			->left_join('CONS_ADDRESS', 'mail_address', array('ADDRESS' => 'mail_ADDRESS', 'CITY' => 'mail_CITY', 'STATE' => 'mail_STATE', 'ZIPCODE' => 'mail_ZIPCODE'), 'mail_address.ADDRESS_ID = stucon.MAILING_ADDRESS_ID')
			->left_join('CONS_PHONE', 'phone', array('PHONE_NUMBER'), 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
			->join('STUD_SECTION', 'section', array('SECTION_ID', 'SECTION_NUMBER'), 'section.SECTION_ID = class.SECTION_ID')
			->join('STUD_COURSE', 'course', array('COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', null, 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgterm.STAFF_ID')
			->predicate($dropped_conditions)
			;
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$result = $result->predicate('status.ORGANIZATION_TERM_ID', $org_term_ids);
		
		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$result = $result->predicate('status.STUDENT_STATUS_ID', $record_id);

		$result = $result
			->order_by('LAST_NAME', 'ASC', 'stucon')
			->order_by('FIRST_NAME', 'ASC', 'stucon')
			->order_by('STUDENT_STATUS_ID', 'ASC', 'status')
			->order_by('START_DATE', 'ASC', 'term')
			->order_by('SECTION_NUMBER', 'ASC')
			->order_by('SECTION_ID', 'ASC');
		//echo $result->sql();
		//var_dump($result->arguments());
		//die();
		 $result = $result->execute();
		
		$last_student_status_id = 0;
		$credit_total = 0;
		while ($row = $result->fetch()) {
			
			if ($row['mail_ADDRESS'] != '') {
				$row['address']['address'] = $row['mail_ADDRESS'];
				$row['address']['city'] = $row['mail_CITY'];
				$row['address']['state'] = $row['mail_STATE'];
				$row['address']['zipcode'] = $row['mail_ZIPCODE']; 
			} else {
				$row['address']['address'] = $row['res_ADDRESS'];
				$row['address']['city'] = $row['res_CITY'];
				$row['address']['state'] = $row['res_STATE'];
				$row['address']['zipcode'] = $row['res_ZIPCODE'];
			}
			
			if (isset($meetings[$row['SECTION_ID']]))  {
				$row = array_merge($row, $meetings[$row['SECTION_ID']]);
			}
			if ($last_student_status_id != $row['STUDENT_STATUS_ID']) {
				$pdf->credit_row($credit_total);
				$pdf->setData($row);
				$pdf->row_count = 1;
				$credit_total = 0;
				$pdf->StartPageGroup();
				$pdf->AddPage();
			}
			
			$pdf->table_row($row);
			$credit_total += $row['CREDITS_ATTEMPTED'];
			$last_student_status_id = $row['STUDENT_STATUS_ID'];
			$pdf->row_count++;
			$pdf->row_page_count++;
			$pdf->row_total_count++;
		}
		
		$pdf->credit_row($credit_total);
		
    // Closing line
	  return $this->pdfResponse($pdf->Output('','S'));
	
	}
}