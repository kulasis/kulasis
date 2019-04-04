<?php

namespace Kula\HEd\Bundle\GradingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class StudentTranscriptReport extends BaseReport {
  
  private $data;
  
  public $angle = 0;

  // Current column
  private $col = 0;
  // Ordinate of column start
  private $y0;
  
  public $minorLabelCalled;

  public $transcript_type = 'unofficial';
  
  public function __construct($orientation='P', $unit='mm', $size='Letter') {
    parent::__construct($orientation, $unit, $size);

    $this->SetMargins(10, 12);
    $this->SetAutoPageBreak(true, 30);
  }
  
  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    // School Logo
    //$image1 = KULA_ROOT . "/core/images/ocaclogo_vertical.png";
    if ($this->transcript_type == 'unofficial') {
      //Put the watermark
      $this->SetFont('Arial','B',80);
      $this->SetTextColor(255,192,203);
      $this->RotatedText(35,190,'U N O F F I C I A L',45);
    }

    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial', '', 8);

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
    $this->Cell(30,5,'Student ID ','LTR',0,'L');
    $this->Cell(30,5,'Date of Birth ','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $this->Cell(30,5, $this->data['PERMANENT_NUMBER'],'LBR',0,'L');
    $this->Cell(30,5, ($this->data['BIRTH_DATE'] != '') ? date('m/d', strtotime($this->data['BIRTH_DATE'])) : '','LBR',0,'L');
    $this->Ln(5);
    $this->SetFont('Arial', '', 8);
    $this->Cell(196,5,'Current Program','LTR',0,'L');
    $this->Ln(4);
    $this->SetFont('Arial', '', 10);
    $program = ($this->data['PRINTED_DEGREE_NAME'] != '') ? $this->data['PRINTED_DEGREE_NAME'] : $this->data['DEGREE_NAME'] ;
    if ($this->data['areas'] != '') $program .= ' / '.$this->data['areas'];
    $this->Cell(196,5, $program, 'LBR', 0,'L');
    $program = '';
    $this->SetFont('Arial', '', 8);
    $this->Ln(10);

    $y_start_ch = $this->GetY();

    $this->SetY(12);
    $this->Cell(0, 5, 'Academic Transcript', 0, 0, 'C');
    $this->Ln(5);
    $this->Cell(0, 5, 'Issue Date: '.date("m/d/y"), 0, 0, 'C');
    $this->Ln(10);
    
    $this->SetLeftMargin(147);
    $this->SetX(147);
    $this->SetY(12);
    
    // College Information
    $this->Cell(60,5, $this->reportInstitutionName, '', 0,'R');
    $this->Ln(4);
    $this->Cell(60,5, $this->reportAddressLine1, '', 0,'R');
    $this->Ln(4);
    $this->Cell(60,5, $this->reportAddressLine2, '', 0,'R');
    $this->Ln(4);
    $this->Cell(60,5, $this->reportPhoneLine1, '', 0,'R');
    $this->Ln(6);
    
    $this->SetFont('Arial', '', 7);
    // Start columns for ch
    $this->SetY($y_start_ch);
    $this->SetLeftMargin(10);
    $this->SetX(10);
    //$this->MultiCell(0, 3, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, 'L');
    $this->Cell(20,6,'Crs ID','LTB',0,'L');
    $this->Cell(60,6,'Course Title','TB',0,'L');
    $this->Cell(5,6,'Mk','TB',0,'L');
    $this->Cell(13,6,'Credit','TB',0,'R');
    $this->Cell(20,6,'Crs ID','TB',0,'L');
    $this->Cell(60,6,'Course Title','TB',0,'L');
    $this->Cell(5,6,'Mk','TB',0,'L');
    $this->Cell(13,6,'Credit','RTB',0,'R');
    $this->Ln(8);
    
    // Save ordinate
    $this->y0 = $this->GetY();
    $this->SetCol(0);
    
    // Middle column line
    $this->Line(108.09, 53, 108.09, 250);
    // bottom line
    $this->Line(10, 250, 206, 250);
  }
  
  public function degree_row($row) {
    $this->SetFont('Arial', '', 7);
    $this->Cell(25,3,'Degree Awarded: ',0,0,'L');
    $this->Cell(55,3,$row['DEGREE_NAME'],0,0,'L');
    $this->Cell(18,3,date('m/d/Y', strtotime($row['GRADUATION_DATE'])),0,0,'R');
    $this->Ln(3);
  }
  
  public function degree_area_row($row) {
    $this->SetFont('Arial', '', 7);
    if (!$this->minorLabelCalled)
      $this->Cell(25,3, $row['AREA_TYPE'].': ',0,0,'L');
    else {
      $this->minorLabelCalled = true;
      $this->Cell(25,3,'',0,0,'L');
    }
    $this->Cell(55,3,($row['PRINTED_AREA_NAME'] != '') ? $row['PRINTED_AREA_NAME'] : $row['AREA_NAME'],0,0,'L');
    $this->Ln(3);
  }

  public function term_table_row($term, $org, $comments = null) {
    $this->SetFont('Arial', '', 7);
    if (isset($org['NON_ORGANIZATION_NAME']) AND $org['NON_ORGANIZATION_NAME'] != '') {
      $this->Cell(63,3,$org['NON_ORGANIZATION_NAME'],0,0,'L');
      if ($term['CALENDAR_MONTH'] || $term['CALENDAR_YEAR']) 
        $this->Cell(15,3,$term['CALENDAR_MONTH'].'/'.$term['CALENDAR_YEAR'],0,0,'R');
      else
        $this->Cell(15,3,'',0,0,'R');  
      $this->Cell(20,3,$term['TERM'],0,0,'R');
    } else {
      $this->Cell(20,3,$term['TERM'],0,0,'L');
      if ($term['CALENDAR_MONTH'] || $term['CALENDAR_YEAR']) 
        $this->Cell(15,3,$term['CALENDAR_MONTH'].'/'.$term['CALENDAR_YEAR'],0,0,'L');
      else
        $this->Cell(15,3,'',0,0,'L');
      $this->Cell(63,3,isset($org['ORGANIZATION_NAME']) ? $org['ORGANIZATION_NAME'] : '',0,0,'R');
    }
    $this->Ln(3);
    if ($comments === null) {
      // Comments
      if (isset($term['COMMENTS']))
        $this->MultiCell(98, 3, $term['COMMENTS']);
      // Standings
      if (isset($term['standings']))
        $this->MultiCell(98, 3, implode(", ", $term['standings']));
    }
  }
  
  public function ch_table_row($row) {
    $this->SetFont('Arial', '', 7);
    $this->Cell(20,3,$row['COURSE_NUMBER'],0,0,'L');
    $this->Cell(60,3,substr($row['COURSE_TITLE'], 0, 50),0,0,'L');
    $this->Cell(5,3,$row['MARK'],0,0,'L');
    if ($row['CREDITS_EARNED'])
      $this->Cell(13,3,sprintf('%0.2f', round($row['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    else
      $this->Cell(13,3,'',0,0,'R');
    $this->Ln(3);
  }
  
  public function gpa_table_row($totals) {
    if ($totals) {
    $this->Ln(2);
    // Header
    $this->Cell(15,3,'',0,0,'R');
    $this->Cell(15,3,'ATT',0,0,'R');
    $this->Cell(15,3,'ERN',0,0,'R');
    $this->Cell(15,3,'HRS',0,0,'R');
    $this->Cell(15,3,'PTS',0,0,'R');
    $this->Cell(15,3,'GPA',0,0,'R');
    $this->Ln(3);
    // Term
    $this->Cell(15,3,'TERM:',0,0,'L');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TERM_CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TERM_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TERM_HOURS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TERM_POINTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TERM_GPA'], 2, PHP_ROUND_HALF_UP)),0,0,'R');  
    $this->Ln(3);
    // Cum
    $this->Cell(15,3,'CUM:',0,0,'L');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_HOURS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_POINTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_GPA'], 2, PHP_ROUND_HALF_UP)),0,0,'R');     
    $this->Ln(3);
    $this->Ln(5); 
    }
  }
  
  public function level_total_gpa_table_row($totals) {
    if ($totals) {

    $this->Ln(2);
    // Header
    $this->add_header(strtoupper($totals['level_description']).' TOTALS');
    $this->Cell(15,3,'' ,0,0,'L');
    $this->Cell(15,3,'ATT',0,0,'R');
    $this->Cell(15,3,'ERN',0,0,'R');
    $this->Cell(15,3,'HRS',0,0,'R');
    $this->Cell(15,3,'PTS',0,0,'R');
    $this->Cell(15,3,'GPA',0,0,'R');
    $this->Ln(3);
    // institution
    $this->Cell(15,3,'INSTITUTION:',0,0,'L');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['INST_CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['INST_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['INST_HOURS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['INST_POINTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['INST_GPA'], 2, PHP_ROUND_HALF_UP)),0,0,'R');     
    $this->Ln(3);
    // transfer
    $this->Cell(15,3,'TRANSFER:',0,0,'L');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TRNS_CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TRNS_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TRNS_HOURS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TRNS_POINTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['TRNS_GPA'], 2, PHP_ROUND_HALF_UP)),0,0,'R');  
    $this->Ln(3);
    // total
    $this->Cell(15,3,'TOTAL:',0,0,'L');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_HOURS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_POINTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    $this->Cell(15,3,sprintf('%0.2f', round($totals['CUM_GPA'], 2, PHP_ROUND_HALF_UP)),0,0,'R');    
    $this->Ln(3);
    $this->Ln(5);
    }
  }
  
  public function currentschedule_term_table_row($row) {
    $this->SetFont('Arial', '', 7);
    $this->Cell(20,3,$row['TERM_NAME'],0,0,'L');
    $this->Cell(15,3,'',0,0,'L');  
    $this->Cell(63,3,$row['ORGANIZATION_NAME'],0,0,'R');
    $this->Ln(3);
  }
  
  public function currentschedule_table_row($row) {
    $this->SetFont('Arial', '', 7);
    $this->Cell(20,3,$row['COURSE_NUMBER'],0,0,'L');
    $this->Cell(60,3,substr($row['COURSE_TITLE'], 0, 50),0,0,'L');
    $this->Cell(5,3,'',0,0,'L');
    if ($row['CREDITS_ATTEMPTED'])
      $this->Cell(13,3,sprintf('%0.2f', round($row['CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    else
      $this->Cell(13,3,'',0,0,'R');
    $this->Ln(3);
  }
  
  public function add_header($title) {
    $this->SetFont('Arial', '', 7);
    
    //if ($this->GetX() > 100) {
      $this->Cell(1,5, '','',0,'C');
      $this->Cell(96,5, $title,'TB',0,'C');
      //} else {
      //$this->Cell(97,5, $title,'LTBR',0,'C');
      //}
    $this->Ln(6);
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
    // Position at 1.5 cm from bottom
    $this->SetY(-28);
    
    $this->SetLeftMargin(10);
    $this->SetRightMargin(10);
    $this->SetX(10);
    
    if ($this->transcript_type == 'official') {
      $this->MultiCell(0,3,'The Family Educational Rights and Privacy Act of 1974 (as amended) prohibits the release of this information without the student\'s written consent. An official transcript must include the signature of the registrar, printing on watermarked paper, and the embossed seal of the college or university. This document reports academic information only.');
      $this->Ln(5);
      // Page number
      $this->Cell(90,4,'Page '.$this->GroupPageNo().' of '.$this->PageGroupAlias(),0,0,'L');
      $this->Cell(85,4,'School Official\'s Signature: _______________________________________',0,0,'L');
      $this->Cell(20,4,'Date: ' . date("m/d/y") ,0,0,'R');
    }

    if ($this->transcript_type == 'unofficial') {
      $this->MultiCell(0,3,'The Family Educational Rights and Privacy Act of 1974 (as amended) prohibits the release of this information without the student\'s written consent. An official transcript would be printed on security paper and bear the signature of the registrar. This document reports academic information only.');
      $this->Ln(5);
      // Page number
      $this->Cell(90,4,'Page '.$this->GroupPageNo().' of '.$this->PageGroupAlias(),0,0,'L');
    }
  }
  
  public function _endpage()
{
    if($this->angle!=0)
    {
        $this->angle=0;
        $this->_out('Q');
    }
    parent::_endpage();
}
  
}