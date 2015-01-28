<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\ReportController;

class FinalGradesReportController extends ReportController {
	
	private $pdf;
	
	public function indexAction() {
		$this->authorize();
		if ($this->request->query->get('record_type') == 'STUDENT_STATUS' AND $this->request->query->get('record_id') != '')
			$this->setRecordType('STUDENT_STATUS');
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		return $this->render('KulaHEdCourseHistoryBundle:FinalGradesReport:reports_finalgradesreport.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$this->pdf = new \Kula\Bundle\HEd\CourseHistoryBundle\Controller\FinalGradesReport("P");
		$this->pdf->SetFillColor(245,245,245);
		$this->pdf->row_count = 0;
		
		// Get Data and Load
		$result = $this->db()->select('STUD_STUDENT', 'student')
			->fields('student', array('STUDENT_ID'))
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID', 'LEVEL' => 'status_LEVEL'), 'status.STUDENT_ID = student.STUDENT_ID')
			->left_join('CORE_LOOKUP_VALUES', 'grvalue', array('DESCRIPTION' => 'GRADE'), 'grvalue.CODE = status.GRADE AND grvalue.LOOKUP_ID = 20')
			->left_join('STUD_STUDENT_DEGREES', 'studdegrees', null, 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
			->left_join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'advisororgterm', null, 'advisororgterm.STAFF_ORGANIZATION_TERM_ID = status.ADVISOR_ID')
			->left_join('STUD_STAFF', 'advisor', array('ABBREVIATED_NAME' => 'advisor_abbreviated_name'), 'advisor.STAFF_ID = advisororgterm.STAFF_ID')
			->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
			->left_join('CONS_ADDRESS', 'home_address', array('ADDRESS' => 'home_ADDRESS', 'CITY' => 'home_CITY', 'STATE' => 'home_STATE', 'ZIPCODE' => 'home_ZIPCODE'), 'home_address.CONSTITUENT_ID = stucon.CONSTITUENT_ID AND home_address.UNDELIVERABLE = \'N\' AND home_address.SEND_GRADES = \'Y\'')
			->left_join('CONS_PHONE', 'phone', array('PHONE_NUMBER'), 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
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
			->order_by('FIRST_NAME', 'ASC', 'stucon');

		$result = $result
		  ->execute();
		
		$last_student_status_id = 0;
		$last_student_id = 0;
		$totals = array();
		$levels_for_cum = array();
		while ($row = $result->fetch()) {
			
				$row['address']['address'] = $row['home_ADDRESS'];
				$row['address']['city'] = $row['home_CITY'];
				$row['address']['state'] = $row['home_STATE'];
				$row['address']['zipcode'] = $row['home_ZIPCODE']; 

			$this->createGradeReport($row['STUDENT_STATUS_ID'], $row['STUDENT_ID'], $row);
			
		}
		
		
    // Closing line
	  return $this->pdfResponse($this->pdf->Output('','S'));
	
	}
	
	public function createGradeReport($student_status_id, $student_id, $data) {
		
		if (!isset($levels_for_cum) OR (isset($levels_for_cum) AND count($levels_for_cum) == 0))
			$levels_for_cum[] = $data['status_LEVEL'];
		$this->pdf->setData($data);
		$this->pdf->row_count = 1;
		
		$totals = array();
		$levels_for_cum = array();
		
		$this->pdf->StartPageGroup();
		$this->pdf->AddPage();
		
		$grades = $this->getGrades($student_status_id);
    
		foreach($grades as $row) {
			$this->pdf->table_row($row);
		
			if (!isset($totals[$row['LEVEL']]['TERM'])) {
				$totals[$row['LEVEL']]['TERM'] = array(
				  'ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0
				);
      }
			
			$totals[$row['LEVEL']]['TERM']['ATT'] += $row['CREDITS_ATTEMPTED'];
			$totals[$row['LEVEL']]['TERM']['ERN'] += $row['CREDITS_EARNED'];
			if ($row['GPA_VALUE'] != '')
				$totals[$row['LEVEL']]['TERM']['HRS'] += $row['CREDITS_ATTEMPTED'];
			$totals[$row['LEVEL']]['TERM']['PTS'] += $row['QUALITY_POINTS'];
			
			$levels_for_cum[] = $row['LEVEL'];
			$this->pdf->row_count++;
			$this->pdf->row_page_count++;
			$this->pdf->row_total_count++;
		}

		$this->getCumulativeTotals($student_id, $levels_for_cum, $totals);
		$this->pdf->gpa_table_row($totals);
	}
	
	public function getGrades($student_status_id) {
		
		$dropped_conditions = new \Kula\Component\Database\Query\Predicate('OR');
		$dropped_conditions = $dropped_conditions->predicate('class.DROPPED', null);
		$dropped_conditions = $dropped_conditions->predicate('class.DROPPED', 'N');
		
		// Get Data and Load
		$result = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array())
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID', 'LEVEL' => 'status_LEVEL'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->left_join('CORE_LOOKUP_VALUES', 'grvalue', array('DESCRIPTION' => 'GRADE'), 'grvalue.CODE = status.GRADE AND grvalue.LOOKUP_ID = 20')
			->left_join('STUD_STUDENT_COURSE_HISTORY', 'stucrshis', array('LEVEL', 'MARK', 'GPA_VALUE', 'QUALITY_POINTS', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'COURSE_NUMBER', 'COURSE_TITLE', 'COMMENTS'), 'stucrshis.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
			->left_join('STUD_STUDENT_DEGREES', 'studdegrees', null, 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
			->left_join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'advisororgterm', null, 'advisororgterm.STAFF_ORGANIZATION_TERM_ID = status.ADVISOR_ID')
			->left_join('STUD_STAFF', 'advisor', array('ABBREVIATED_NAME' => 'advisor_abbreviated_name'), 'advisor.STAFF_ID = advisororgterm.STAFF_ID')
			->join('STUD_SECTION', 'section', array('SECTION_ID', 'SECTION_NUMBER'), 'section.SECTION_ID = class.SECTION_ID')
			->join('STUD_COURSE', 'course', array('COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', null, 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgterm.STAFF_ID')
			->predicate($dropped_conditions)
			->predicate('status.STUDENT_STATUS_ID', $student_status_id)
			;
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$result = $result->predicate('status.ORGANIZATION_TERM_ID', $org_term_ids);
		
		$result = $result->order_by('START_DATE', 'ASC', 'term')
			->order_by('SECTION_NUMBER', 'ASC')
			->order_by('SECTION_ID', 'ASC');

		$result = $result
			->execute();
		
		return $result->fetchAll();
	}
	
	public function getCumulativeTotals($student_id, $levels_for_cum, &$totals) {
		
		$cum_result = $this->db()->select('STUD_STUDENT_COURSE_HISTORY', 'crshis')
			->fields('crshis', array('NON_ORGANIZATION_ID', 'LEVEL', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'GPA_VALUE', 'QUALITY_POINTS', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED'))
			->predicate('STUDENT_ID', $student_id);
		if (count($levels_for_cum)) {
			$cum_result = $cum_result->predicate('LEVEL', $levels_for_cum);
		}
		$cum_result = $cum_result->execute();
	
    //$totals['']['CUM'] = array('ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0);
    
		while ($cum_row = $cum_result->fetch()) {
			
			if (!isset($totals[$cum_row['LEVEL']]['CUM'])) {
				$totals[$cum_row['LEVEL']]['CUM'] = array('ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0);
			}
      
			$totals[$cum_row['LEVEL']]['CUM']['ATT'] += $cum_row['CREDITS_ATTEMPTED'];
			$totals[$cum_row['LEVEL']]['CUM']['ERN'] += $cum_row['CREDITS_EARNED'];
			if ($cum_row['GPA_VALUE'] != '' AND !$cum_row['NON_ORGANIZATION_ID'])
				$totals[$cum_row['LEVEL']]['CUM']['HRS'] += $cum_row['CREDITS_ATTEMPTED'];
			$totals[$cum_row['LEVEL']]['CUM']['PTS'] += $cum_row['QUALITY_POINTS'];
			
		}
	}
}