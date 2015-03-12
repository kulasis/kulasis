<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISStudentTranscriptReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    $this->formAction('sis_HEd_student_coursehistory_reports_studenttranscript_generate');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'SIS.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('SIS.HEd.Student');
    return $this->render('KulaHEdGradingBundle:SISStudentTranscriptReport:reports_studenttranscript.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $pdf = new \Kula\HEd\Bundle\GradingBundle\Report\StudentTranscriptReport("P");
    $pdf->SetFillColor(245,245,245);
    $pdf->row_count = 0;
    
    $current_schedules = array();
    $current_schedules_count = array();
    
    // Add on level
    $level_condition = '';
    $level = $this->request->request->get('non');
    if (isset($level['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']) AND $level['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level'] != '') {
      $level_condition = ' AND classes.LEVEL = \''.$level['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level'].'\'';
    }
    
    // Get schedule data
    $schedule_result = $this->db()->db_select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = student.STUDENT_ID')
      ->join('STUD_STUDENT_CLASSES', 'classes', 'classes.STUDENT_STATUS_ID = stustatus.STUDENT_STATUS_ID '.$level_condition)
      ->fields('classes', array('LEVEL', 'CREDITS_ATTEMPTED'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = classes.SECTION_ID')
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_NAME'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'level_values', "level_values.CODE = classes.LEVEL AND level_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Level')")
      ->fields('level_values', array('DESCRIPTION' => 'LEVEL_DESCRIPTION'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', 'stucoursehistory.STUDENT_CLASS_ID = classes.STUDENT_CLASS_ID')
      ->condition('stucoursehistory.COURSE_HISTORY_ID', null)
      ->condition('DROPPED', 0)
      ;
    //$org_term_ids = $this->session->get('organization_term_ids');
    //if (isset($org_term_ids) AND count($org_term_ids) > 0) {
    //  $schedule_result = $schedule_result->condition('stustatus.ORGANIZATION_TERM_ID', $org_term_ids);
    //}
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $schedule_result = $schedule_result->condition('student.STUDENT_ID', $record_id);
    $schedule_result = $schedule_result
      ->orderBy('student.STUDENT_ID', 'ASC')
      ->orderBy('level_values.DESCRIPTION', 'ASC')
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('course.COURSE_NUMBER', 'ASC')
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
    if (isset($level['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']) AND $level['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level'] != '') {
      $level_condition = ' AND coursehistory.LEVEL = \''.$level['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level'].'\'';
    }
    
    $row_counts = array();
    // Get counts of rows
    $result_counts = $this->db()->db_select('STUD_STUDENT', 'student')
      ->distinct()
      ->fields('student', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', 'coursehistory.STUDENT_ID = student.STUDENT_ID '.$level_condition)
      ->fields('coursehistory', array('LEVEL', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'NON_ORGANIZATION_ID'))
      ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = coursehistory.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->leftJoin('CORE_NON_ORGANIZATION', 'nonorg', 'nonorg.NON_ORGANIZATION_ID = coursehistory.NON_ORGANIZATION_ID')
      ->fields('nonorg', array('NON_ORGANIZATION_NAME'))
      ;
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result_counts = $result_counts->condition('student.STUDENT_ID', $record_id);

    $result_counts = $result_counts
      ->groupBy('student.STUDENT_ID')
      ->groupBy('coursehistory.LEVEL')
      ->groupBy('org.ORGANIZATION_NAME')
      ->groupBy('nonorg.NON_ORGANIZATION_NAME')
      ->groupBy('coursehistory.CALENDAR_YEAR')
      ->groupBy('coursehistory.CALENDAR_MONTH')
      ->groupBy('coursehistory.TERM')
      ->orderBy('student.STUDENT_ID', 'ASC')
      ->expression('COUNT(*)', 'row_count');
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
    $result = $this->db()->db_select('STUD_STUDENT', 'student')
      ->distinct()
      ->fields('student', array('STUDENT_ID', 'ORIGINAL_ENTER_DATE', 'HIGH_SCHOOL_GRADUATION_DATE'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'BIRTH_DATE'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', 'coursehistory.STUDENT_ID = student.STUDENT_ID '.$level_condition)
      ->fields('coursehistory', array('ORGANIZATION_ID', 'LEVEL', 'COURSE_NUMBER', 'COURSE_TITLE', 'MARK', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'QUALITY_POINTS', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'NON_ORGANIZATION_ID', 'TRANSFER_CREDITS'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'level_values', "level_values.CODE = coursehistory.LEVEL AND level_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Level')")
      ->fields('level_values', array('DESCRIPTION' => 'LEVEL_DESCRIPTION'))
      ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = coursehistory.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->leftJoin('CORE_NON_ORGANIZATION', 'nonorg', 'nonorg.NON_ORGANIZATION_ID = coursehistory.NON_ORGANIZATION_ID')
      ->fields('nonorg', array('NON_ORGANIZATION_NAME'))
      ;
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->leftJoin('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = student.STUDENT_ID');
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids);
    }
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->condition('student.STUDENT_ID', $record_id);

    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->orderBy('student.STUDENT_ID', 'ASC')
      ->orderBy('coursehistory.LEVEL', 'ASC')
      ->orderBy('coursehistory.CALENDAR_YEAR', 'ASC')
      ->orderBy('coursehistory.CALENDAR_MONTH', 'ASC')
      ->orderBy('coursehistory.TERM', 'ASC')
      ->orderBy('nonorg.NON_ORGANIZATION_NAME', 'ASC')
      ->orderBy('org.ORGANIZATION_NAME', 'ASC')
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
        $status_info = $this->db()->db_select('STUD_STUDENT_STATUS', 'status')
          ->fields('status')
          ->join('CORE_LOOKUP_VALUES', 'grade_values', "grade_values.CODE = status.GRADE AND grade_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
          ->fields('grade_values', array('DESCRIPTION' => 'GRADE'))
          ->leftJoin('STUD_STUDENT_DEGREES', 'studdegrees', 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
          ->leftJoin('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = studdegrees.DEGREE_ID')
          ->fields('degree', array('DEGREE_NAME'))
          ->leftJoin('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentrations', 'studdegrees.STUDENT_DEGREE_ID = stuconcentrations.STUDENT_DEGREE_ID')
          ->leftJoin('STUD_DEGREE_CONCENTRATION', 'concentrations', 'stuconcentrations.CONCENTRATION_ID = concentrations.CONCENTRATION_ID')
          ->fields('concentrations', array('CONCENTRATION_NAME'))
          ->condition('status.STUDENT_ID', $row['STUDENT_ID']);
        
        if (isset($level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']) AND $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'] != '') {
         $status_info = $status_info->condition('status.LEVEL', $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']);  
        }
        
        $status_info = $status_info->orderBy('ENTER_DATE', 'DESC')
          ->execute()->fetch();
        
        // Standings info
        $standings_info = array();
        
        $standings_info_result = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY_STANDING', 'chstanding')
          ->fields('chstanding', array('CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM'))
          ->join('STUD_STANDING', 'standing', 'chstanding.STANDING_ID = standing.STANDING_ID')
          ->fields('standing', array('STANDING_DESCRIPTION'))
          ->condition('chstanding.STUDENT_ID', $row['STUDENT_ID'])->execute();
        while ($standings_info_row = $standings_info_result->fetch()) {
          $standings_info['standings'][$standings_info_row['CALENDAR_YEAR']][$standings_info_row['CALENDAR_MONTH']][$standings_info_row['TERM']] = $standings_info_row['STANDING_DESCRIPTION'];
        }
        
        // Comments info
        $comments_info = array();
        
        $comments_info_result = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY_TERMS', 'chcomment')
          ->fields('chcomment', array('CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'LEVEL', 'COMMENTS'))
          ->condition('chcomment.STUDENT_ID', $row['STUDENT_ID']);
        if (isset($level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']) AND $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL'] != '') {
         $comments_info_result = $comments_info_result->condition('chcomment.LEVEL', $level['STUD_STUDENT_COURSE_HISTORY']['LEVEL']);  
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
        $degrees_res = $this->db()->db_select('STUD_STUDENT_DEGREES', 'studdegrees')
          ->fields('studdegrees', array('STUDENT_DEGREE_ID', 'DEGREE_AWARDED', 'GRADUATION_DATE', 'CONFERRED_DATE'))
          ->join('STUD_DEGREE', 'degree', 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
          ->fields('degree', array('DEGREE_NAME'))
          ->condition('studdegrees.STUDENT_ID', $row['STUDENT_ID'])
          ->condition('studdegrees.DEGREE_AWARDED', 1)
          ->condition('degree.LEVEL', $row['LEVEL'])
          ->execute();
        while ($degree_row = $degrees_res->fetch()) {
          
          // Check how far from bottom
          $current_y = $pdf->GetY();
          if (260 - $current_y < 8) {
            $pdf->Ln(260 - $current_y);
          }
          
          $pdf->degree_row($degree_row);  
          // Get majors
          $degree_majors_res = $this->db()->db_select('STUD_STUDENT_DEGREES_MAJORS', 'studmajors')
            ->fields('studmajors', array())
            ->join('STUD_DEGREE_MAJOR', 'degmajor', 'degmajor.MAJOR_ID = studmajors.MAJOR_ID')
            ->fields('degmajor', array('MAJOR_NAME'))
            ->condition('studmajors.STUDENT_DEGREE_ID', $degree_row['STUDENT_DEGREE_ID'])
            ->execute();
          while ($degree_major_row = $degree_majors_res->fetch()) {
            $pdf->degree_major_row($degree_major_row);  
          }
          // Get minors
          $degree_minors_res = $this->db()->db_select('STUD_STUDENT_DEGREES_MINORS', 'studminors')
            ->fields('studminors', array())
            ->join('STUD_DEGREE_MINOR', 'degminor', 'degminor.MINOR_ID = studminors.MINOR_ID')
            ->fields('degminor', array('MINOR_NAME'))
            ->condition('studminors.STUDENT_DEGREE_ID', $degree_row['STUDENT_DEGREE_ID'])
            ->execute();
          while ($degree_minor_row = $degree_minors_res->fetch()) {
            $pdf->degree_minor_row($degree_minor_row);  
          }
          // Get concentrations
          $degree_concentrations_res = $this->db()->db_select('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'studconcentrations')
            ->fields('studconcentrations')
            ->join('STUD_DEGREE_CONCENTRATION', 'degconcentration', 'degconcentration.CONCENTRATION_ID = studconcentrations.CONCENTRATION_ID')
            ->fields('degconcentration', array('CONCENTRATION_NAME'))
            ->condition('studconcentrations.STUDENT_DEGREE_ID', $degree_row['STUDENT_DEGREE_ID'])
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
        if (isset($row_counts[$row['STUDENT_ID']][$row['LEVEL']][$organization_name][$row['CALENDAR_YEAR']][$row['CALENDAR_MONTH']][$row['TERM']])) {
          $height_for_rows = $row_counts[$row['STUDENT_ID']][$row['LEVEL']][$organization_name][$row['CALENDAR_YEAR']][$row['CALENDAR_MONTH']][$row['TERM']] * 4;
        } else {
          $height_for_rows = 0;
        }
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
      if ($row['TRANSFER_CREDITS'] == 0)
        $totals['CUM']['HRS'] += $row['CREDITS_ATTEMPTED'];
      $totals['CUM']['PTS'] += $row['QUALITY_POINTS'];
      
      $totals['TERM']['ATT'] += $row['CREDITS_ATTEMPTED'];
      $totals['TERM']['ERN'] += $row['CREDITS_EARNED'];
      if ($row['TRANSFER_CREDITS'] == 0)
        $totals['TERM']['HRS'] += $row['CREDITS_ATTEMPTED'];
      $totals['TERM']['PTS'] += $row['QUALITY_POINTS'];
      
      if ($row['TRANSFER_CREDITS']) {
        $totals['transfer']['ATT'] += $row['CREDITS_ATTEMPTED'];
        $totals['transfer']['ERN'] += $row['CREDITS_EARNED'];
      } elseif ($row['TRANSFER_CREDITS'] == 0) {
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