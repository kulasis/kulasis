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
    $this->Cell(60,5, $this->data['degrees'],'LBR',0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    $this->Cell(60,5, 'Area(s)','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->MultiCell(60,15, $this->data['areas'],'LBR','L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    
    $y_start_ch = $this->GetY();
    
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
    $this->Cell(8,3,sprintf('%0.2f', round($row['CREDITS_REQUIRED'], 2, PHP_ROUND_HALF_UP)), 'T',0,'R');
    $this->Cell(5,6,'','T',0,'L');
    $this->Cell(15,6,'','RT',0,'L');
    $this->Ln(3);
  }
  
  public function req_grp_row($req_id, $row) {
    
    if ((isset($row['SHOW_AS_OPTION']) AND $row['SHOW_AS_OPTION'] AND isset($row['display_credits'])) OR (isset($row['display_credits']) AND $row['display_credits'] != '')) {
    
      $this->SetFont('Arial', '', 7);
    
      $this->Cell(9,3,isset($row['TERM_ABBREVIATION']) ? strtoupper($row['TERM_ABBREVIATION']) : '','L',0,'L');
      $this->Cell(3,3,(isset($row['REQUIRED']) AND $row['REQUIRED']) ? 'R' : '',0,0,'L');
      $this->Cell(13,3,$row['COURSE_NUMBER'],0,0,'L');
      $this->Cell(40,3,substr($row['COURSE_TITLE'], 0, 35),0,0,'L');
      $this->Cell(13,3,isset($row['display_credits']) ? sprintf('%0.2f', round($row['display_credits'], 2, PHP_ROUND_HALF_UP)) : '',0,0,'R');
      $this->Cell(5,3,isset($row['MARK']) ? $row['MARK'] : '',0,0,'L');
      $this->Cell(15,3,isset($row['status']) ? $row['status'] : '','R',0,'L');
      $this->Ln(3);
    
    }
  
  }
  
  public function req_grp_footer_row($req_id, $row) {
    $this->SetFont('Arial', '', 7);
    $this->Cell(30,3, isset($row['credits_earned']) ? 'Credits Completed: ' . sprintf('%0.2f', round($row['credits_earned'], 2, PHP_ROUND_HALF_UP)) : '','LB',0,'L');
    $this->Cell(68,3, isset($row['credits_remain']) ? 'Credits Remaining: '. sprintf('%0.2f', round($row['credits_remain'], 2, PHP_ROUND_HALF_UP)) : '','RB',0,'L');
    $this->Ln(7);
  }
  
  public function degree_footer_row() {
    $this->SetFont('Arial', '', 7);
    $this->Cell(30,3,'Total Credits Minimum: '.sprintf('%0.2f', round($this->total_degree_needed, 2, PHP_ROUND_HALF_UP)),0,0,'L');
    $this->Ln(3);
    $this->Cell(30,3,'Total Credits Completed: '.sprintf('%0.2f', round($this->total_degree_completed, 2, PHP_ROUND_HALF_UP)),0,0,'L');
    $this->Ln(3);
    $this->Cell(20,3,'Total Credits Remaining: '.sprintf('%0.2f', round($this->total_degree_remaining, 2, PHP_ROUND_HALF_UP)),0,0,'L');
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