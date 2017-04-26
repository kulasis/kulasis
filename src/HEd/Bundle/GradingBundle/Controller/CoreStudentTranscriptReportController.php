<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;
use Kula\Core\Component\Lookup\Lookup;

class CoreStudentTranscriptReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    //$this->formAction('sis_HEd_student_coursehistory_reports_studenttranscript_generate');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    
    $lookup_service = $this->get('kula.core.lookup');
    $levels = $lookup_service->getLookupMenu('HEd.Student.Enrollment.Level', 'D');

    if ($this->request->query->get('record_type') == 'Core.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student');
    return $this->render('KulaHEdGradingBundle:CoreStudentTranscriptReport:reports_studenttranscript.html.twig', array('levels' => $levels));
  }
  
  public function generateAction()
  {  
    $this->authorize();
    $this->service = $this->get('kula.HEd.grading.transcript');

    $pdf = new \Kula\HEd\Bundle\GradingBundle\Report\StudentTranscriptReport("P");
    $pdf->SetFillColor(245,245,245);
    $pdf->row_count = 0;
    
    // Get Data and Load
    $result = $this->db()->db_select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID');
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->leftJoin('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = student.STUDENT_ID');
      $result = $result->fields('status', array('LEVEL'));
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids);
    }
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->condition('student.STUDENT_ID', $record_id);

    $non = $this->request->request->get('non');
    if (isset($non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']) AND $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level'] != '') {
      $result = $result->condition('status.LEVEL', $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']);
    }

    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->orderBy('student.STUDENT_ID', 'ASC')
      //->range(0, 1)
      ->execute();
    
    while ($row = $result->fetch()) {

      if (isset($non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']) AND $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level'] != '') {
        $this->service->loadTranscriptForStudent($row['STUDENT_ID'], $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']);
      } else {
        $this->service->loadTranscriptForStudent($row['STUDENT_ID'], null);   
      }

      $data = $this->service->getTranscriptData();
      $current_schedule = $this->service->getCurrentScheduleData();
      $degree_data = $this->service->getDegreeData();
      $student_data = $this->service->getStudentData();

      $pdf->setData($student_data);
      $pdf->row_count = 1;
      $pdf->pageNum = 1;
      $pdf->pageTotal = 1;
      $pdf->StartPageGroup();
      $pdf->AddPage();

      // Check how far from bottom
      $current_y = $pdf->GetY();
      if (260 - $current_y < 30) {
        $pdf->Ln(260 - $current_y);
      }

      // Load 
      foreach($data['levels'] as $levelcode => $level) {

        if ($levelcode != '') {

        // load degrees
        if (isset($degree_data[$levelcode])) {
        foreach($degree_data[$levelcode] as $degree) {
           $pdf->degree_row($degree);

           if (isset($degree['areas'])) {
           foreach($degree['areas'] as $area) {
            $pdf->degree_area_row($area);
           }
           }
           $pdf->Ln(3);
        }
        }

        $pdf->add_header(strtoupper($level['level_description']).' COURSEWORK');

        foreach($level['terms'] as $term) {

          // Check how far from bottom
          $current_y = $pdf->GetY();
          if (isset($term['courses'])) {
            $height_for_rows = count($term['courses']) * 4;
          } else {
            $height_for_rows = 0;
          }
          $total_height = $height_for_rows + 4 + 4 + 4 + 2 + 4 + 4;

          if (260 - $current_y < $total_height) {
            $pdf->Ln(260 - $current_y);
          }

          $count_for_orgs_in_term = 0;
          foreach($term['orgs'] as $org) {

            $pdf->term_table_row($term, $org, ($count_for_orgs_in_term > 0) ? 'N' : null);

            foreach($org['courses'] as $course) {

              $pdf->ch_table_row($course);

            } // end foreach on courses

          $count_for_orgs_in_term++;
          }

          

          // Add on GPA totals for term
          $pdf->gpa_table_row($term);

        } // end foreach on terms

        // Check how far from bottom
        $current_y = $pdf->GetY();
        if (260 - $current_y < 30) {
          $pdf->Ln(260 - $current_y);
        }

        // Add on GPA totals for level
        $pdf->level_total_gpa_table_row($level);
        // Add on comments
        
        if ($level['COMMENTS'] != '') {
          $pdf->Ln(5);
          $pdf->Cell(98, 5, 'Comments', '', 0, 'L');
          $pdf->Ln(3);
          $pdf->Cell(98, 5, $level['COMMENTS'], '', 0, 'L');
        }

        } // end if on level

      } // end foreach on level

      // Add on current schedule
      foreach($current_schedule as $level => $level_row) {
        $loop = 0;
        foreach($level_row as $org_name => $org_row) {
          foreach($org_row as $term_name => $term_row) {

            // Check how far from bottom
            $amount_to_check = count($term_row) * 3 + 3 + 3 + 5 + 20;
            $current_y = $pdf->GetY();
            if (270 - $current_y < $amount_to_check) {
              $pdf->Ln(270 - $current_y);
            }

            if ($loop == 0) {
              $pdf->add_header(strtoupper($level).' COURSES IN PROGRESS');
            }
            
            $pdf->currentschedule_term_table_row(array('TERM_NAME' => $term_name, 'ORGANIZATION_NAME' => $org_name));
            foreach($term_row as $schedule_row) {
              $pdf->currentschedule_table_row($schedule_row);
            }
            $pdf->Ln(3);
            $loop = 1;
          } // end foreach on term
        } // end foreach on organization
      } // end foreach on level

    } // end while on students

    // Closing line
    return $this->pdfResponse($pdf->Output('','S'));
  
  }
}