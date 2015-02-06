<?php

namespace Kula\HEd\Bundle\GradingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class DegreeAuditReport extends BaseReport {
  
  private $data;
  public $course_history_data;
  public $req_grp_totals;
  public $total_degree_needed;
  public $total_degree_completed;
  
  // Current column
  private $col = 0;
  // Ordinate of column start
  private $y0;
  
  public $minorLabelCalled;
  
  public function __construct($orientation='P', $unit='mm', $size='Letter') {
    parent::__construct($orientation, $unit, $size);

    $this->SetMargins(10, 12);
    $this->SetAutoPageBreak(true, 20);
  }
  
  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    // School Logo
    //$image1 = KULA_ROOT . "/core/images/ocaclogo_vertical.png";

    $middle_initial = substr($this->data['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    
    // Student Information
    $this->Cell(60,5, 'Student Name', 'LTR', 0, 'L'); // Left Box
    //$this->Image($image1, 97, 10); // Center Logo
    $this->Ln(4);
    $this->SetFont('Arial', 'B', 10);
    $this->Cell(60,5, $this->data['LAST_NAME'].', '.$this->data['FIRST_NAME'] . ' ' . $middle_initial, 'LBR', 0,'L');
    
    
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    $this->Cell(20,5,'Student ID ','LTR',0,'L');
    $this->Cell(40,5,'Grade ','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->Cell(20,5, $this->data['PERMANENT_NUMBER'],'LBR',0,'L');
    $this->Cell(40,5, $this->data['GRADE'],'LBR',0,'L');
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    $this->Cell(20,5,'Gender ','LTR',0,'L');
    $this->Cell(40,5,'Level','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->Cell(20,5, $this->data['GENDER'],'LBR',0,'L');
    $this->Cell(40,5, $this->data['LEVEL'],'LBR',0,'L');
    $this->Ln(5);
    
    $this->SetY(12);
    $this->SetLeftMargin(146);
    $this->SetX(146);
    
    $this->SetFont('Arial', '', 8);
    $this->Cell(60,5, 'Program ','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->Cell(60,5, $this->data['DEGREE_NAME'],'LBR',0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    $this->Cell(60,5, 'Major(s)','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->Cell(60,5, implode(", ", $this->data['majors']),'LBR',0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    $this->Cell(60,5, 'Minor(s)','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->Cell(60,5, implode(", ", $this->data['minors']),'LBR',0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    $this->Cell(60,5, 'Concentration(s)','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->Cell(60,5, implode(", ", $this->data['concentrations']),'LBR',0,'L');
    $this->SetFont('Arial', '', 8);
    
    $y_start_ch = $this->GetY() + 5;
    
    $this->SetLeftMargin(10);
    $this->SetX(10);
  
    $this->SetY(12);
    $this->Cell(0, 5, 'Degree Audit', 0, 0, 'C');
    $this->Ln(10);
    
    $this->SetLeftMargin(70);
    $this->SetX(70);
    if ($this->data['EXPECTED_GRADUATION_DATE']) {
      $this->Cell(40,5,'Expected Graduation Date: ','',0,'L');
      $this->Cell(10,5,date('m/d/Y', strtotime($this->data['EXPECTED_GRADUATION_DATE'])),'',0,'L');
      $this->Ln(4);
    }
    if ($this->data['expected_completion_term']) {
      $this->Cell(40,5,'Expected Completion Term: ','',0,'L');
      $this->Cell(10,5,$this->data['expected_completion_term'],'',0,'L');
      $this->Ln(4);
    }

    $this->SetFont('Arial', '', 7);
    // Start columns for ch
    $this->SetY($y_start_ch);
    $this->SetLeftMargin(10);
    $this->SetX(10);
    //$this->MultiCell(0, 3, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, 'L');
    $this->Cell(9,6,'Term','LTB',0,'L');
    $this->Cell(3,6,'R','TB',0,'L');
    $this->Cell(13,6,'Crs ID','TB',0,'L');
    $this->Cell(40,6,'Course Title','TB',0,'L');
    $this->Cell(13,6,'Credit','TB',0,'R');
    $this->Cell(5,6,'Mk','TB',0,'L');
    $this->Cell(15,6,'Status','TB',0,'L');
    
    $this->Cell(9,6,'Term','TB',0,'L');
    $this->Cell(3,6,'R','TB',0,'L');
    $this->Cell(13,6,'Crs ID','TB',0,'L');
    $this->Cell(40,6,'Course Title','TB',0,'L');
    $this->Cell(13,6,'Credit','TB',0,'R');
    $this->Cell(5,6,'Mk','TB',0,'L');
    $this->Cell(15,6,'Status','RTB',0,'L');
    
    $this->Ln(8);
    
    // Save ordinate
    $this->y0 = $this->GetY();
    $this->SetCol(0);
    
  }


  public function req_grp_header_row($req_id, $row) {
    $this->SetFont('Arial', '', 7);
    $this->Cell(65,3,$row['GROUP_NAME'],'LT',0,'L');
    $this->Cell(5, 3, 'Min:', 'T', 0, 'R');
    $this->Cell(8,3,sprintf('%0.2f', round($row['CREDITS_REQUIRED'])), 'T',0,'R');
    $this->Cell(5,6,'','T',0,'L');
    $this->Cell(15,6,'','RT',0,'L');
    $this->Ln(3);
    $this->total_degree_needed += $row['CREDITS_REQUIRED'];
  }
  
  public function req_grp_row($req_id, $row, $elective = null) {
    
    if (!isset($this->req_grp_totals[$req_id])) $this->req_grp_totals[$req_id] = 0;
    $this->SetFont('Arial', '', 7);
    //if ($elective) die();
    //if ($this->req_grp_totals[$req_id] + $this->course_history_data[$row['COURSE_ID']]['CREDITS_EARNED'] <= $row['CREDITS_REQUIRED'] || $elective) {
    
      // elective
      if ($elective) {
        foreach($row as $ch_index => $ch) {
          if ($ch['COURSE_ID']) {
        if (isset($ch['TERM_ABBREVIATION'])) 
          $this->Cell(9,3,strtoupper($ch['TERM_ABBREVIATION']),'L',0,'L');
        else
          $this->Cell(9,3,'','L',0,'L');
      
        if (isset($ch['REQUIRED']) AND $ch['REQUIRED'] == 'Y')
          $this->Cell(3,3,'R',0,0,'L');
        else
          $this->Cell(3,3,'',0,0,'L');
  
        $this->Cell(13,3,$ch['COURSE_NUMBER'],0,0,'L');
        $this->Cell(40,3,substr($ch['COURSE_TITLE'], 0, 35),0,0,'L');
        // Transfer credit
        if (isset($ch['STUDENT_CLASS_ID'])) {
          $this->Cell(13,3,isset($ch['CREDITS']) ? sprintf('%0.2f', round($ch['CREDITS'], 2, PHP_ROUND_HALF_UP)) : '',0,0,'R');
        } else {
          $this->Cell(13,3,isset($ch['CREDITS_EARNED']) ? sprintf('%0.2f', round($ch['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)) : '',0,0,'R');
        }
        
        $this->Cell(5,3,isset($ch['MARK']) ? $ch['MARK'] : '',0,0,'L');
        
        if (isset($ch['STUDENT_CLASS_ID'])) {
          $this->Cell(15,3,'In Prog','R',0,'L');
        } else {
          $this->Cell(15,3,'Comp','R',0,'L');
        }
        $this->Ln(3);
        $this->course_history_data[$ch['COURSE_ID']][0]['used'] = 'Y';
        $this->req_grp_totals[$req_id] += isset($ch['CREDITS_EARNED']) ? $ch['CREDITS_EARNED'] : 0;
        }
      }
        
      } elseif (isset($this->course_history_data[$row['COURSE_ID']]) AND count($this->course_history_data) > 0) {
        
        foreach($this->course_history_data[$row['COURSE_ID']] as $ch_index => $ch) {
          
          if ((!isset($this->course_history_data[$row['COURSE_ID']][$ch_index]['used']) OR 
              $this->course_history_data[$row['COURSE_ID']][$ch_index]['used'] != 'Y') AND 
              (!isset($ch['DEGREE_REQ_GRP_ID']) OR (isset($ch['DEGREE_REQ_GRP_ID']) AND ($ch['DEGREE_REQ_GRP_ID'] == '' OR $ch['DEGREE_REQ_GRP_ID'] == $req_id))))
         {
        
        if (isset($ch['TERM_ABBREVIATION'])) 
          $this->Cell(9,3,strtoupper($ch['TERM_ABBREVIATION']),'L',0,'L');
        else
          $this->Cell(9,3,'','L',0,'L');
      
        if (isset($row['REQUIRED']) AND $row['REQUIRED'] == 'Y')
          $this->Cell(3,3,'R',0,0,'L');
        else
          $this->Cell(3,3,'',0,0,'L');
  
        $this->Cell(13,3,$ch['COURSE_NUMBER'],0,0,'L');
        $this->Cell(40,3,substr($ch['COURSE_TITLE'], 0, 35),0,0,'L');
        
        if (isset($ch['STUDENT_CLASS_ID'])) {
          $this->Cell(13,3,sprintf('%0.2f', round($ch['CREDITS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
          $this->Cell(5,3,'',0,0,'L');
          $this->Cell(15,3,'In Prog','R',0,'L');
        } elseif (isset($row['CREDITS']) AND $ch['CREDITS_ATTEMPTED'] > $ch['CREDITS_EARNED']) {
          $this->Cell(13,3,sprintf('%0.2f', round($ch['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
          $this->Cell(5,3,$ch['MARK'],0,0,'L');
          $this->Cell(15,3,'Remain','R',0,'L');
        } else {
          $this->Cell(13,3,sprintf('%0.2f', round($ch['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
          $this->Cell(5,3,$ch['MARK'],0,0,'L');
          $this->Cell(15,3,'Comp','R',0,'L');
          $this->req_grp_totals[$req_id] += $ch['CREDITS_EARNED'];
        }
        $this->Ln(3);
        $this->course_history_data[$row['COURSE_ID']][$ch_index]['used'] = 'Y';
        
        }
        
        }
        
      } elseif (isset($row['equivs']) AND $equiv_course = array_map(array($this, 'check_if_equiv_exists'), $row['equivs']) AND $equiv_course[0] != '') {

      
        foreach($equiv_course[0] as $ch_index => $ch) {
        
        if (!isset($this->course_history_data[$row['COURSE_ID']][$ch_index]['used']) OR $this->course_history_data[$row['COURSE_ID']][$ch_index]['used'] != 'Y') {
        
        if (isset($ch['TERM_ABBREVIATION'])) 
          $this->Cell(9,3,strtoupper($ch['TERM_ABBREVIATION']),'L',0,'L');
        else
          $this->Cell(9,3,'','L',0,'L');
      
        if ($row['REQUIRED'] == 'Y')
          $this->Cell(3,3,'R',0,0,'L');
        else
          $this->Cell(3,3,'',0,0,'L');
        
        $this->Cell(13,3,$ch['COURSE_NUMBER'],0,0,'L');
        $this->Cell(40,3,substr($ch['COURSE_TITLE'], 0, 35),0,0,'L');
        $this->Cell(13,3,sprintf('%0.2f', round($ch['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
        $this->Cell(5,3,$ch['MARK'],0,0,'L');
        $this->Cell(15,3,'Comp','R',0,'L');
        $this->req_grp_totals[$req_id] += $ch['CREDITS_EARNED'];
        $this->course_history_data[$ch['COURSE_ID']][$ch_index]['used'] = 'Y';
        $this->Ln(3);
        }
        }
      } elseif (isset($row['CREDITS_EARNED']) AND $row['CREDITS_EARNED'] > 0) {
        
        foreach($this->course_history_data[$ch_course['COURSE_ID']] as $ch_index => $ch) {
        
        if (!isset($this->course_history_data[$row['COURSE_ID']][$ch_index]['used']) OR $this->course_history_data[$row['COURSE_ID']][$ch_index]['used'] != 'Y') {
        
        if (isset($ch['TERM_ABBREVIATION'])) 
          $this->Cell(9,3,strtoupper($ch['TERM_ABBREVIATION']),'L',0,'L');
        else
          $this->Cell(9,3,'','L',0,'L');
      
        if (isset($row['REQUIRED']) AND $row['REQUIRED'] == 'Y')
          $this->Cell(3,3,'R',0,0,'L');
        else
          $this->Cell(3,3,'',0,0,'L');
  
        $this->Cell(13,3,$ch['COURSE_NUMBER'],0,0,'L');
        $this->Cell(40,3,substr($ch['COURSE_TITLE'], 0, 35),0,0,'L');
        // Transfer credit
        $this->Cell(13,3,sprintf('%0.2f', round($row['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
        $this->Cell(5,3,isset($row['MARK']) ? $row['MARK'] : '',0,0,'L');
        $this->Cell(15,3,'Comp','R',0,'L');
        $this->Ln(3);
        $this->course_history_data[$ch_course['COURSE_ID']][$ch_index]['used'] = 'Y';
        $this->req_grp_totals[$req_id] += $row['CREDITS_EARNED'];
        }
        }
      // if nothing
      } else {
        
        if (isset($row['SHOW_AS_OPTION']) AND $row['SHOW_AS_OPTION'] == 'Y') {
        $this->Cell(9,3,'','L',0,'L');
      
        if (isset($row['REQUIRED']) AND $row['REQUIRED'] == 'Y')
          $this->Cell(3,3,'R',0,0,'L');
        else
          $this->Cell(3,3,'',0,0,'L');
  
        $this->Cell(13,3,$row['COURSE_NUMBER'],0,0,'L');
        $this->Cell(40,3,substr($row['COURSE_TITLE'], 0, 35),0,0,'L');
        // Not set
        $this->Cell(13,3,sprintf('%0.2f', round($row['CREDITS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
        $this->Cell(5,3,isset($row['MARK']) ? $row['MARK'] : '',0,0,'L');
        if (isset($row['REQUIRED']) AND $row['REQUIRED'] == 'Y')
          $this->Cell(15,3,'Remain','R',0,'L');
        else
          $this->Cell(15,3,'','R',0,'L');
        $this->Ln(3);
      }
    }
  }
  
  public function check_if_equiv_exists($equiv) {
    if (isset($this->course_history_data[$equiv])) {
      return $this->course_history_data[$equiv];
    }
  }
  
  public function req_grp_footer_row($req_id, $row) {
    $this->SetFont('Arial', '', 7);
    if (!isset($this->req_grp_totals[$req_id]))
      $this->req_grp_totals[$req_id] = 0;
    $this->total_degree_completed += $this->req_grp_totals[$req_id];
    $this->Cell(30,3,'Credits Completed: '.sprintf('%0.2f', round($this->req_grp_totals[$req_id])),'LB',0,'L');
    $this->Cell(68,3,'Credits Remaining: '.sprintf('%0.2f', round($row['CREDITS_REQUIRED'] - $this->req_grp_totals[$req_id])),'RB',0,'L');
    $this->Ln(7);
  }
  
  public function degree_footer_row() {
    $this->SetFont('Arial', '', 7);
    $this->Cell(30,3,'Total Credits Minimum: '.sprintf('%0.2f', round($this->total_degree_needed)),0,0,'L');
    $this->Ln(3);
    $this->Cell(30,3,'Total Credits Completed: '.sprintf('%0.2f', round($this->total_degree_completed)),0,0,'L');
    $this->Ln(3);
    $this->Cell(20,3,'Total Credits Remaining: '.sprintf('%0.2f', round($this->total_degree_needed - $this->total_degree_completed)),0,0,'L');
    $this->Ln(7);
  }
  
  public function SetCol($col) {
    // Set position at a given column
    $this->col = $col;
    $x = 10+$col*98;
    $this->SetLeftMargin($x);
    $this->SetX($x);
  }
  
  public function AcceptPageBreak() {
      // Method accepting or not automatic page break
      if($this->col<1)
      {
          // Go to next column
          $this->SetCol($this->col+1);
          // Set ordinate to top
          $this->SetY($this->y0);
          // Keep on page
          return false;
      }
      else
      {
          // Go back to first column
          $this->SetCol(0);
          // Page break
          return true;
      }
  }
  
  public function Footer()
  {
    $this->SetLeftMargin(10);
    $this->SetRightMargin(10);
    $this->SetX(10);
    
    $this->include_footer_info = true;
    parent::Footer();
  }
  
  
  
}