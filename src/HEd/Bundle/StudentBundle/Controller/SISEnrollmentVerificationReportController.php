<?php

namespace Kula\Bundle\HEd\StudentBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\ReportController;

class EnrollmentVerificationReportController extends ReportController {
	
	public function indexAction() {
		$this->authorize();
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		if ($this->request->query->get('record_type') == 'STUDENT' AND $this->request->query->get('record_id') != '')
			$this->setRecordType('STUDENT');
		return $this->render('KulaHEdStudentBundle:EnrollmentVerificationReport:reports_enrollmentverification.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$pdf = new \Kula\Bundle\HEd\StudentBundle\Controller\EnrollmentVerificationReport("P");
		$pdf->SetFillColor(245,245,245);
		$pdf->row_count = 0;

		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		$record_type = $this->request->request->get('record_type');
		$credit_totals_array = array();
		
		$predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
		$predicate_or = $predicate_or->predicate('class.DROPPED', null)->predicate('class.DROPPED', 'N');
		
		// Credit Totals for Term
		$credit_totals = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_STATUS_ID'))
			->expressions(array('SUM(CREDITS_ATTEMPTED)' => 'total_credits_attempted'))
			->join('STUD_STUDENT_STATUS', 'status', null, 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->predicate($predicate_or)
			->group_by('STUDENT_STATUS_ID', 'class');
			if (isset($record_id) AND $record_id != '' AND $record_type == 'STUDENT')
				$credit_totals = $credit_totals->predicate('status.STUDENT_ID', $record_id);
		$credit_totals = $credit_totals->execute();
		while ($credit_total_row = $credit_totals->fetch()) {
			$credit_totals_array[$credit_total_row['STUDENT_STATUS_ID']] = $credit_total_row['total_credits_attempted'];
		}
		
		// Get Data and Load
		$result = $this->db()->select('STUD_STUDENT_STATUS', 'status')
			->fields('status', array('STUDENT_ID', 'STUDENT_STATUS_ID', 'FTE', 'ENTER_DATE', 'LEAVE_DATE'))
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION', 'START_DATE', 'END_DATE'), 'term.TERM_ID = orgterms.TERM_ID')
			->left_join('STUD_STUDENT_DEGREES', 'studdegrees', array('EXPECTED_GRADUATION_DATE'), 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
			->left_join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'degree.DEGREE_ID = studdegrees.DEGREE_ID')
			->left_join('STUD_SCHOOL_TERM_LEVEL', 'schooltermlevel', array('MIN_FULL_TIME_HOURS'), 'schooltermlevel.LEVEL = status.LEVEL AND schooltermlevel.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID');
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$result = $result->predicate('status.ORGANIZATION_TERM_ID', $org_term_ids);
		
		// Add on selected record
		if (isset($record_id) AND $record_id != '' AND $record_type == 'STUDENT')
			$result = $result->predicate('status.STUDENT_ID', $record_id);
		
		$result = $result
			->order_by('LAST_NAME', 'ASC', 'stucon')
			->order_by('FIRST_NAME', 'ASC', 'stucon')
			->order_by('STUDENT_ID', 'ASC', 'student')
			->order_by('START_DATE', 'DESC', 'term')
		  ->execute();
		
		$last_student_id = 0;
		$credit_total = 0;
		while ($row = $result->fetch()) {
			if ($last_student_id != $row['STUDENT_ID']) {
				if ($last_student_id !== 0) $pdf->bottom_content();
				$pdf->setData($row);
				$pdf->row_count = 1;
				$pdf->AddPage();
				$pdf->content();
			}
			
			if (isset($credit_totals_array[$row['STUDENT_STATUS_ID']])) {
				$row = array_merge($row, array('credits_attempted' => $credit_totals_array[$row['STUDENT_STATUS_ID']]));
			} else {
				$row = array_merge($row, array('credits_attempted' => ''));	
			}
			
			$pdf->table_row($row);
			$last_student_id = $row['STUDENT_ID'];
			$pdf->row_count++;
			$pdf->row_page_count++;
			$pdf->row_total_count++;
		}
		
		$pdf->bottom_content();
    // Closing line
	  return $this->pdfResponse($pdf->Output('','S'));
	
	}
}