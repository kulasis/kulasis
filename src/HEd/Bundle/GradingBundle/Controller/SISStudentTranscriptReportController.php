<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\ReportController;

class StudentTranscriptReportController extends ReportController {
	
	public function indexAction() {
		$this->authorize();
		$this->formAction('sis_student_coursehistory_reports_studenttranscript_generate');
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		if ($this->request->query->get('record_type') == 'STUDENT' AND $this->request->query->get('record_id') != '')
			$this->setRecordType('STUDENT');
		return $this->render('KulaHEdCourseHistoryBundle:StudentTranscriptReport:reports_studenttranscript.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$pdf = new \Kula\Bundle\HEd\CourseHistoryBundle\Controller\StudentTranscriptReport("P");
		$pdf->SetFillColor(245,245,245);
		$pdf->row_count = 0;
		
		$current_schedules = array();
		$current_schedules_count = array();
		
		// Add on level
		$level_condition = '';
		$level = $this->request->request->get('non');
		if (isset($level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']) AND $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'] != '') {
			$level_condition = ' AND classes.LEVEL = \''.$level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'].'\'';
		}
		
		$query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
		$query_conditions = $query_conditions->predicate('DROPPED', null);
		$query_conditions = $query_conditions->predicate('DROPPED', 'N');
		// Get schedule data
		$schedule_result = $this->db()->select('STUD_STUDENT', 'student')
			->fields('student', array('STUDENT_ID'))
			->join('STUD_STUDENT_STATUS', 'stustatus', null, 'stustatus.STUDENT_ID = student.STUDENT_ID')
			->join('STUD_STUDENT_CLASSES', 'classes', array('LEVEL', 'CREDITS_ATTEMPTED'), 'classes.STUDENT_STATUS_ID = stustatus.STUDENT_STATUS_ID '.$level_condition)
			->join('STUD_SECTION', 'section', null, 'section.SECTION_ID = classes.SECTION_ID')
			->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_NAME'), 'term.TERM_ID = orgterms.TERM_ID')
			->left_join('CORE_LOOKUP_VALUES', 'level_values', array('DESCRIPTION' => 'LEVEL_DESCRIPTION'), 'level_values.CODE = classes.LEVEL AND level_values.LOOKUP_ID = 37')
			->left_join('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', null, 'stucoursehistory.STUDENT_CLASS_ID = classes.STUDENT_CLASS_ID')
			->predicate('stucoursehistory.COURSE_HISTORY_ID', null)
			->predicate($query_conditions)
			;
		//$org_term_ids = $this->session->get('organization_term_ids');
		//if (isset($org_term_ids) AND count($org_term_ids) > 0) {
		//	$schedule_result = $schedule_result->predicate('stustatus.ORGANIZATION_TERM_ID', $org_term_ids);
		//}
		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$schedule_result = $schedule_result->predicate('student.STUDENT_ID', $record_id);
		$schedule_result = $schedule_result
			->order_by('STUDENT_ID', 'ASC', 'student')
			->order_by('DESCRIPTION', 'ASC', 'level_values')
			->order_by('START_DATE', 'ASC', 'term')
			->order_by('COURSE_NUMBER', 'ASC', 'course')
			;
		$schedule_result = $schedule_result->execute();
		while ($schedule_row = $schedule_result->fetch()) {
			$current_schedules[$schedule_row['STUDENT_ID']][$schedule_row['LEVEL_DESCRIPTION']][$schedule_row['ORGANIZATION_NAME']][$schedule_row['TERM_NAME']][] = $schedule_row;
			if (!isset($current_schedules_count[$schedule_row['STUDENT_ID']][$schedule_row['LEVEL_DESCRIPTION']][$schedule_row['ORGANIZATION_NAME']][$schedule_row['TERM_NAME']]))
				$current_schedules_count[$schedule_row['STUDENT_ID']][$schedule_row['LEVEL_DESCRIPTION']][$schedule_row['ORGANIZATION_NAME']][$schedule_row['TERM_NAME']] = 0;
			$current_schedules_count[$schedule_row['STUDENT_ID']][$schedule_row['LEVEL_DESCRIPTION']][$schedule_row['ORGANIZATION_NAME']][$schedule_row['TERM_NAME']]++;
		}
		
		// Add on level
		$level_condition = '';
		$level = $this->request->request->get('non');
		if (isset($level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']) AND $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'] != '') {
			$level_condition = ' AND coursehistory.LEVEL = \''.$level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'].'\'';
		}
		
		$row_counts = array();
		// Get counts of rows
		$result_counts = $this->db()->select('STUD_STUDENT', 'student')
			->distinct()
			->fields('student', array('STUDENT_ID'))
			->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
			->left_join('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', array('LEVEL', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'NON_ORGANIZATION_ID'), 'coursehistory.STUDENT_ID = student.STUDENT_ID '.$level_condition)
			->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'org.ORGANIZATION_ID = coursehistory.ORGANIZATION_ID')
			->left_join('CORE_NON_ORGANIZATION', 'nonorg', array('NON_ORGANIZATION_NAME'), 'nonorg.NON_ORGANIZATION_ID = coursehistory.NON_ORGANIZATION_ID')
			;
		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$result_counts = $result_counts->predicate('student.STUDENT_ID', $record_id);

		$result_counts = $result_counts
			->group_by('STUDENT_ID', 'student')
			->group_by('LEVEL', 'coursehistory')
			->group_by('ORGANIZATION_NAME', 'org')
			->group_by('NON_ORGANIZATION_NAME', 'nonorg')
			->group_by('CALENDAR_YEAR', 'coursehistory')
			->group_by('CALENDAR_MONTH', 'coursehistory')
			->group_by('TERM', 'coursehistory')
			->order_by('STUDENT_ID', 'ASC', 'student')
			->expressions(array('COUNT(*)' => 'row_count'));
		$result_counts = $result_counts->execute();
		
		while ($counts_row = $result_counts->fetch()) {
			
			if ($counts_row['NON_ORGANIZATION_NAME'])
				$organization_name = $counts_row['NON_ORGANIZATION_NAME'];
			else
				$organization_name = $counts_row['ORGANIZATION_NAME'];
			
			$row_counts[$counts_row['STUDENT_ID']][$counts_row['LEVEL']][$organization_name][$counts_row['CALENDAR_YEAR']][$counts_row['CALENDAR_MONTH']][$counts_row['TERM']] = $counts_row['row_count'];

			unset($organization_name);
		}
		
		// Get Data and Load
		$result = $this->db()->select('STUD_STUDENT', 'student')
			->distinct()
			->fields('student', array('STUDENT_ID', 'ORIG_ENTER_DATE', 'HIGH_SCHOOL_GRADUATION_DATE'))
			->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'BIRTH_DATE'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
			->left_join('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', array('ORGANIZATION_ID', 'LEVEL', 'COURSE_NUMBER', 'COURSE_TITLE', 'MARK', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'QUALITY_POINTS', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'NON_ORGANIZATION_ID'), 'coursehistory.STUDENT_ID = student.STUDENT_ID '.$level_condition)
			->left_join('CORE_LOOKUP_VALUES', 'level_values', array('DESCRIPTION' => 'LEVEL_DESCRIPTION'), 'level_values.CODE = coursehistory.LEVEL AND level_values.LOOKUP_ID = 37')
			->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'org.ORGANIZATION_ID = coursehistory.ORGANIZATION_ID')
			->left_join('CORE_NON_ORGANIZATION', 'nonorg', array('NON_ORGANIZATION_NAME'), 'nonorg.NON_ORGANIZATION_ID = coursehistory.NON_ORGANIZATION_ID')
			;
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0) {
			$result = $result->left_join('STUD_STUDENT_STATUS', 'status', null, 'status.STUDENT_ID = student.STUDENT_ID');
			$result = $result->predicate('status.ORGANIZATION_TERM_ID', $org_term_ids);
		}
		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		if (isset($record_id) AND $record_id != '')
			$result = $result->predicate('student.STUDENT_ID', $record_id);

		$result = $result
			->order_by('LAST_NAME', 'ASC', 'stucon')
			->order_by('FIRST_NAME', 'ASC', 'stucon')
			->order_by('STUDENT_ID', 'ASC', 'student')
			->order_by('LEVEL', 'ASC', 'coursehistory')
			->order_by('CALENDAR_YEAR', 'ASC', 'coursehistory')
			->order_by('CALENDAR_MONTH', 'ASC', 'coursehistory')
			->order_by('TERM', 'ASC', 'coursehistory')
			->order_by('NON_ORGANIZATION_NAME', 'ASC', 'nonorg')
			->order_by('ORGANIZATION_NAME', 'ASC', 'org')
			->execute();
		
		$last_student_id = 0;
		$last_calendar_month = 0;
		$last_calendar_year = 0;
		$last_term = 0;
		$last_level = '';
		$last_level_code = '';
		$last_non_organization_name = '';
		$last_organization_name = '';
		
		$totals = array();
		
		while ($row = $result->fetch()) {

			if ($last_student_id != $row['STUDENT_ID']) {
				if ($last_student_id !== 0 AND $last_level != '') {
					// Load last items for student
					$pdf->gpa_table_row($totals);
					
					// Check how far from bottom
					$current_y = $pdf->GetY();
					if (260 - $current_y < 30) {
						$pdf->Ln(260 - $current_y);
					}
					
					$pdf->level_total_gpa_table_row($totals, $last_level);
					// Add on comments
					if (isset($row['comments'][''][''][''])) {
							$pdf->Ln(5);
							$pdf->Cell(98, 5, 'Comments', '', 0, 'L');
							$pdf->Ln(3);
							$pdf->Cell(98, 5, $row['comments'][''][''][''], '', 0, 'L');
					}
				}
				
				// Add on current schedule
				if ($last_level != '')
					$pdf->currentschedule($current_schedules, $last_student_id, $last_level, $current_schedules_count);
				else
					$pdf->currentschedule($current_schedules, $last_student_id, $row['LEVEL_DESCRIPTION'], $current_schedules_count);
				
				$last_calendar_month = 0; $last_calendar_year = 0; $last_term = 0; $last_level = ''; $last_level_code = '';
				$totals['CUM']['ATT'] = 0.0;
				$totals['CUM']['ERN'] = 0.0;
				$totals['CUM']['HRS'] = 0.0;
				$totals['CUM']['PTS'] = 0.0;
				$totals['TERM']['ATT'] = 0.0;
				$totals['TERM']['ERN'] = 0.0;
				$totals['TERM']['HRS'] = 0.0;
				$totals['TERM']['PTS'] = 0.0;
				
				$totals['transfer']['ATT'] = 0.0;
				$totals['transfer']['ERN'] = 0.0;
				$totals['transfer']['HRS'] = 0.0;
				$totals['transfer']['PTS'] = 0.0;
				
				$totals['institution']['ATT'] = 0.0;
				$totals['institution']['ERN'] = 0.0;
				$totals['institution']['HRS'] = 0.0;
				$totals['institution']['PTS'] = 0.0;	
				
				// Grade status info
				$status_info = $this->db()->select('STUD_STUDENT_STATUS', 'status')
					->fields('status', array())
					->join('CORE_LOOKUP_VALUES', 'grade_values', array('DESCRIPTION' => 'GRADE'), 'grade_values.CODE = status.GRADE AND grade_values.LOOKUP_ID = 20')
					->left_join('STUD_STUDENT_DEGREES', 'studdegrees', null, 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
					->left_join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'degree.DEGREE_ID = studdegrees.DEGREE_ID')
					->left_join('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentrations', null, 'studdegrees.STUDENT_DEGREE_ID = stuconcentrations.STUDENT_DEGREE_ID')
					->left_join('STUD_DEGREE_CONCENTRATION', 'concentrations', array('CONCENTRATION_NAME'), 'stuconcentrations.CONCENTRATION_ID = concentrations.CONCENTRATION_ID')
					->predicate('status.STUDENT_ID', $row['STUDENT_ID']);
				
				if (isset($level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']) AND $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'] != '') {
				 $status_info = $status_info->predicate('status.LEVEL', $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']);	
				}
				
				$status_info = $status_info->order_by('ENTER_DATE', 'DESC')
					->execute()->fetch();
				
				// Standings info
				$standings_info = array();
				
				$standings_info_result = $this->db()->select('STUD_STUDENT_COURSE_HISTORY_STANDING', 'chstanding')
					->fields('chstanding', array('CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM'))
					->join('STUD_STANDING', 'standing', array('STANDING_DESCRIPTION'), 'chstanding.STANDING_ID = standing.STANDING_ID')
					->predicate('chstanding.STUDENT_ID', $row['STUDENT_ID'])->execute();
				while ($standings_info_row = $standings_info_result->fetch()) {
					$standings_info['standings'][$standings_info_row['CALENDAR_YEAR']][$standings_info_row['CALENDAR_MONTH']][$standings_info_row['TERM']] = $standings_info_row['STANDING_DESCRIPTION'];
				}
				
				// Comments info
				$comments_info = array();
				
				$comments_info_result = $this->db()->select('STUD_STUDENT_COURSE_HISTORY_COMMENT', 'chcomment')
					->fields('chcomment', array('CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'LEVEL', 'COMMENTS'))
					->predicate('chcomment.STUDENT_ID', $row['STUDENT_ID']);
				if (isset($level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']) AND $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'] != '') {
				 $comments_info_result = $comments_info_result->predicate('chcomment.LEVEL', $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']);	
				}
				$comments_info_result = $comments_info_result->execute();
				while ($comments_info_row = $comments_info_result->fetch()) {
					$comments_info['comments'][$comments_info_row['CALENDAR_YEAR']][$comments_info_row['CALENDAR_MONTH']][$comments_info_row['TERM']] = $comments_info_row['COMMENTS'];
				}
				
				$row = array_merge($row, array('GRADE' => '', 'DEGREE_NAME' => ''));
				if (is_array($status_info)) $row = array_merge($row, $status_info, $standings_info, $comments_info);
				
				$pdf->setData($row);
				$pdf->row_count = 1;
				$pdf->pageNum = 1;
				$pdf->pageTotal = 1;
				$pdf->StartPageGroup();
				$pdf->AddPage();
			
			}
			
			if ($last_level != $row['LEVEL_DESCRIPTION']) {
				if ($last_level !== '') { 
					$pdf->gpa_table_row($totals);
					
					// Check how far from bottom
					$current_y = $pdf->GetY();
					if (260 - $current_y < 30) {
						$pdf->Ln(260 - $current_y);
					}
					
					$pdf->level_total_gpa_table_row($totals, $last_level);
					
					// Add on current schedule
					if ($last_level != '')
						$pdf->currentschedule($current_schedules, $last_student_id, $last_level, $current_schedules_count);
					else
						$pdf->currentschedule($current_schedules, $last_student_id, $row['LEVEL_DESCRIPTION'], $current_schedules_count);
					
				}
				if ($level_condition == '') { 
					$pdf->add_header(strtoupper($row['LEVEL_DESCRIPTION']).' COURSEWORK');
				}
				$last_calendar_month = 0; $last_calendar_year = 0; $last_term = 0; 
				$totals['CUM']['ATT'] = 0.0;
				$totals['CUM']['ERN'] = 0.0;
				$totals['CUM']['HRS'] = 0.0;
				$totals['CUM']['PTS'] = 0.0;
				$totals['TERM']['ATT'] = 0.0;
				$totals['TERM']['ERN'] = 0.0;
				$totals['TERM']['HRS'] = 0.0;
				$totals['TERM']['PTS'] = 0.0;
				// Get Degrees
				$degrees_res = $this->db()->select('STUD_STUDENT_DEGREES', 'studdegrees')
					->fields('studdegrees', array('STUDENT_DEGREE_ID', 'DEGREE_AWARDED', 'GRADUATION_DATE', 'CONFERRED_DATE'))
					->join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
					->predicate('studdegrees.STUDENT_ID', $row['STUDENT_ID'])
					->predicate('studdegrees.DEGREE_AWARDED', 'Y')
					->predicate('degree.LEVEL', $row['LEVEL'])
					->execute();
				while ($degree_row = $degrees_res->fetch()) {
					
					// Check how far from bottom
					$current_y = $pdf->GetY();
					if (260 - $current_y < 8) {
						$pdf->Ln(260 - $current_y);
					}
					
					$pdf->degree_row($degree_row);	
					// Get majors
					$degree_majors_res = $this->db()->select('STUD_STUDENT_DEGREES_MAJORS', 'studmajors')
						->fields('studmajors', array())
						->join('STUD_DEGREE_MAJOR', 'degmajor', array('MAJOR_NAME'), 'degmajor.MAJOR_ID = studmajors.MAJOR_ID')
						->predicate('studmajors.STUDENT_DEGREE_ID', $degree_row['STUDENT_DEGREE_ID'])
						->execute();
					while ($degree_major_row = $degree_majors_res->fetch()) {
						$pdf->degree_major_row($degree_major_row);	
					}
					// Get minors
					$degree_minors_res = $this->db()->select('STUD_STUDENT_DEGREES_MINORS', 'studminors')
						->fields('studminors', array())
						->join('STUD_DEGREE_MINOR', 'degminor', array('MINOR_NAME'), 'degminor.MINOR_ID = studminors.MINOR_ID')
						->predicate('studminors.STUDENT_DEGREE_ID', $degree_row['STUDENT_DEGREE_ID'])
						->execute();
					while ($degree_minor_row = $degree_minors_res->fetch()) {
						$pdf->degree_minor_row($degree_minor_row);	
					}
					// Get concentrations
					$degree_concentrations_res = $this->db()->select('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'studconcentrations')
						->fields('studconcentrations', array())
						->join('STUD_DEGREE_CONCENTRATION', 'degconcentration', array('CONCENTRATION_NAME'), 'degconcentration.CONCENTRATION_ID = studconcentrations.CONCENTRATION_ID')
						->predicate('studconcentrations.STUDENT_DEGREE_ID', $degree_row['STUDENT_DEGREE_ID'])
						->execute();
					while ($degree_concentration_row = $degree_concentrations_res->fetch()) {
						$pdf->degree_concentration_row($degree_concentration_row);	
					}
					$pdf->Ln(3);
				}
			}
			
			if ($last_calendar_month !== $row['CALENDAR_MONTH'] || $last_calendar_year !== $row['CALENDAR_YEAR'] || $last_term !== $row['TERM'] || $last_non_organization_name != $row['NON_ORGANIZATION_NAME'] || $last_organization_name != $row['ORGANIZATION_NAME']) {
				if ($last_calendar_month !== 0) $pdf->gpa_table_row($totals);
				
				// Check how far from bottom
				$current_y = $pdf->GetY();
				//echo 'Current Y: '.$current_y.' Current X: '.$pdf->GetX().'<br />';
				if ($row['NON_ORGANIZATION_NAME'] != '')
					$organization_name = $row['NON_ORGANIZATION_NAME'];
				else
					$organization_name = $row['ORGANIZATION_NAME'];
				$height_for_rows = $row_counts[$row['STUDENT_ID']][$row['LEVEL']][$organization_name][$row['CALENDAR_YEAR']][$row['CALENDAR_MONTH']][$row['TERM']] * 4;
				$total_height = $height_for_rows + 4 + 4 + 4 + 2 + 4 + 4;
				//kula_print_r($row_counts);
				//echo $organization_name.' '.$height_for_rows.' '.$total_height.'<br />';
				//die();
				//if ($organization_name == 'OCAC Degree Programs') die();
				
				if (260 - $current_y < $total_height) {
					$pdf->Ln(260 - $current_y);
				}
				
				$pdf->term_table_row($row);
				$totals['TERM']['ATT'] = 0.0;
				$totals['TERM']['ERN'] = 0.0;
				$totals['TERM']['HRS'] = 0.0;
				$totals['TERM']['PTS'] = 0.0;
				unset($organization_name, $current_y, $height_for_rows, $total_height);
			}
			
			$pdf->ch_table_row($row);
			
			$totals['CUM']['ATT'] += $row['CREDITS_ATTEMPTED'];
			$totals['CUM']['ERN'] += $row['CREDITS_EARNED'];
			if ($row['ORGANIZATION_ID'])
				$totals['CUM']['HRS'] += $row['CREDITS_ATTEMPTED'];
			$totals['CUM']['PTS'] += $row['QUALITY_POINTS'];
			
			$totals['TERM']['ATT'] += $row['CREDITS_ATTEMPTED'];
			$totals['TERM']['ERN'] += $row['CREDITS_EARNED'];
			if ($row['ORGANIZATION_ID'])
				$totals['TERM']['HRS'] += $row['CREDITS_ATTEMPTED'];
			$totals['TERM']['PTS'] += $row['QUALITY_POINTS'];
			
			if ($row['NON_ORGANIZATION_ID']) {
				$totals['transfer']['ATT'] += $row['CREDITS_ATTEMPTED'];
				$totals['transfer']['ERN'] += $row['CREDITS_EARNED'];
			} elseif ($row['ORGANIZATION_ID']) {
				$totals['institution']['ATT'] += $row['CREDITS_ATTEMPTED'];
				$totals['institution']['ERN'] += $row['CREDITS_EARNED'];
				$totals['institution']['HRS'] += $row['CREDITS_ATTEMPTED'];
				$totals['institution']['PTS'] += $row['QUALITY_POINTS'];	
			}
			
			
			$last_student_id = $row['STUDENT_ID'];
			$last_calendar_month = $row['CALENDAR_MONTH'];
			$last_calendar_year = $row['CALENDAR_YEAR'];
			$last_term = $row['TERM'];
			$last_level = $row['LEVEL_DESCRIPTION'];
			$last_level_code = $row['LEVEL'];
			$last_non_organization_name = $row['NON_ORGANIZATION_NAME'];
			$last_organization_name = $row['ORGANIZATION_NAME'];
			$pdf->row_count++;
			$pdf->row_page_count++;
			$pdf->row_total_count++;
		}
		
		//if ($last_term != '') {
		$pdf->gpa_table_row($totals);
		
		// Check how far from bottom
		$current_y = $pdf->GetY();
		if (260 - $current_y < 30) {
			$pdf->Ln(260 - $current_y);
		}
		
		$pdf->level_total_gpa_table_row($totals, $last_level);
		//}
		
		// Add on current schedule
		$pdf->currentschedule($current_schedules, $last_student_id, $last_level, $current_schedules_count);
		
    // Closing line
	  return $this->pdfResponse($pdf->Output('','S'));
	
	}
}