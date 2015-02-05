<?php

namespace Kula\HEd\Bundle\StudentBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class EnrollmentVerificationReport extends BaseReport {
  
  private $width = array(15, 30, 30, 30, 30, 30);
  
  private $data;

  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    $this->setReportTitle('Enrollment Verification');
    $this->school_name = $this->data['ORGANIZATION_NAME'];
    $this->term_name = $this->data['TERM_ABBREVIATION'];
    parent::Header();
  }
  
  public function content() {
    // Student Information
    $this->Ln(25);
    $this->Cell(100, 0, 'TO WHOM IT MAY CONCERN:', 0, 0, 'L');
    $this->Ln(5);
    $this->Write(5,'As of ' . date('m/d/Y') . ', we are pleased to certify the following academic information regarding: ');
    $this->Ln(10);
    
    $this->Cell(25, 0, 'Student Name: ', 0, 0, 'R');
    $this->SetFont('Arial', 'B', 8);
    $this->Cell(40,0, $this->data['LAST_NAME'].', '.$this->data['FIRST_NAME'] . ' ' . $this->data['MIDDLE_NAME'], 0, 0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(5);
    $this->Cell(25,0,'Student ID: ',0,0,'R');
    $this->Cell(0,0, $this->data['PERMANENT_NUMBER'],0,0,'L');
    $this->Ln(5);
    $this->Cell(25,0,'Degree Program: ',0,0,'R');
    $this->Cell(0,0, $this->data['DEGREE_NAME'],0,0,'L');
    $this->Ln(8);
    $this->Write(5, $this->reportInstitutionName.' Certification system is designed to expedite the sharing of information with many institutions.  We appreciate your accepting this certification since hand-processed forms slow our response.');
    $this->Ln(8);
    $this->Write(5, 'This verification is valid only when it bears the imprinted seal of '.$this->reportInstitutionName.' and the facsimile signature of the Registrar or Director of Financial Aid.');
    $this->Ln(10);
    // Column headings
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
      $header = array(null, 'Term', 'Beginning', 'Ending', 'Hours', 'Status');
      for($i=0;$i<count($header);$i++)
          $this->Cell($this->width[$i],6,$header[$i],$header[$i] == null ? 0 : 1,0,'C');
      $this->Ln();
  }
  
  public function table_row($row)
  {
    $this->Cell($this->width[0],6,'','',0,'L');
    $this->Cell($this->width[1],6,$row['TERM_ABBREVIATION'],'',0,'L',$this->fill);
    $this->Cell($this->width[2],6,date("m/d/Y", strtotime($row['ENTER_DATE'])),'',0,'L',$this->fill);
    if ($row['LEAVE_DATE'] != '')
      $this->Cell($this->width[3],6,date("m/d/Y", strtotime($row['LEAVE_DATE'])),'',0,'L',$this->fill);
    else
      $this->Cell($this->width[3],6,date("m/d/Y", strtotime($row['END_DATE'])),'',0,'L',$this->fill);
    $this->Cell($this->width[4],6,$row['credits_attempted'],'',0,'L',$this->fill);
    if ($row['credits_attempted'] >= $row['MIN_FULL_TIME_HOURS']) {
      $this->Cell($this->width[5],6,'Full Time','',0,'L',$this->fill);
    } elseif ($row['credits_attempted'] > 0) {
      $this->Cell($this->width[5],6,'Part Time','',0,'L',$this->fill);
    } else {
      $this->Cell($this->width[5],6,'Not Enrolled','',0,'L',$this->fill);  
    }
    $this->Ln();
    $this->fill = !$this->fill;
  }
  
  public function bottom_content() {
    $this->Ln(10);
    $this->Cell(50, 0, 'Expected Graduation Date: ', 0, 0, 'R');
    if ($this->data['EXPECTED_GRADUATION_DATE']) $this->Cell(50, 0, date("m/d/Y", strtotime($this->data['EXPECTED_GRADUATION_DATE'])), 0, 0, 'L');
    $this->Ln(10);
    $this->Write(5, 'As of the date printed below, we certify the above enrollment data: ');
    $this->Ln(15);
    $this->Cell(100,4,'School Official\'s Signature: _______________________________________',0,0,'L');
    $this->Cell(20,4,'Date:   ' . date("l, F d, Y") ,0,0,'L');
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}