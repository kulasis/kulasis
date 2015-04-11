<?php

namespace Kula\K12\Bundle\SchedulingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class ClassRosterSignInOutReport extends BaseReport {
  
  private $data;
  
  public function __construct($orientation='P', $unit='mm', $size='Letter') {
    $this->show_logo = false;
    parent::__construct($orientation, $unit, $size);
  }
  
  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    $this->setReportTitle('CLASS ROSTER SIGN IN/OUT');
    $this->school_name = $this->data['ORGANIZATION_NAME'];
    $this->term_name = $this->data['TERM_ABBREVIATION'];
    parent::Header();
    
    $this->SetY(14);
    $this->Cell(30, 3,$this->data['COURSE_TITLE'].' ('.$this->data['SECTION_NUMBER'].')','',0,'L',$this->fill);
    $this->Ln(4);
    $this->Cell(30, 3,date('m/d/Y', strtotime($this->data['START_DATE'])).' - '.date('m/d/Y', strtotime($this->data['END_DATE'])),'',0,'L',$this->fill);
    
    $this->SetY(20);
    $this->Cell(150, 6, '', '', 0, 'L');
    $this->Cell(50, 6, 'Sign In', '', 0, 'L');
    $this->Cell(50, 6, 'Sign Out', '', 0, 'L');
    $this->Cell(20, 6, '', '', 0, 'L');
    $this->Ln(5);
    // Column headings
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
    $this->Line(15, 25, 265, 25);
  }
  
  public function table_row($row)
  {
    $middle_initial = substr($row['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    $this->Cell(20, 8, 'Student Name:', '', 0, 'L');
    $this->Cell(70, 8,$row['LAST_NAME'].', '.$row['FIRST_NAME'].' '.$middle_initial.' ('.$row['PERMANENT_NUMBER'].')','',0,'L',$this->fill);
    
    $this->Cell(10, 8, 'Group:', '', 0, 'L');
    $this->Cell(10, 8,$this->data['SECTION_NUMBER'],'',0,'L',$this->fill);
    
    $this->Ln(5);
    
    $this->Cell(20, 8, 'Parents:', '', 0, 'L');
    $this->Cell(50, 8,$row['parents'],'',0,'L',$this->fill);
    
    // Checkbox
    $this->Cell(160, 8, '', '', 0, 'L');
    $this->Cell(3, 3, '', 1, 0, 'L');
    $this->Cell(15, 3, 'Check ID', '', 0, 'L');
    
    $this->Ln(5);
    
    $this->Cell(20, 8, 'Drivers:', '', 0, 'L');
    $this->Cell(115, 8,$row['authorized_drivers'],'',0,'L',$this->fill);

    $this->Ln(3);
    $this->Cell(136, 8, '', '', 0, 'L');
    $this->SetFont('Arial','',6);
    $this->Cell(40,4,'Parent Sign In','T',0,'C',$this->fill);
    $this->Cell(10, 8, '', '', 0, 'L');
    $this->Cell(40,4,'Parent/Driver Sign Out','T',0,'C',$this->fill);
    $this->SetFont('Arial','',8);
    
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
    $this->Line(196, $this->GetY()-10, 196, $this->GetY());
    
    $this->Ln(2);
    $this->Cell(250, 4, '', 'B', 0, 'L');
    
    //$this->Cell($this->width[4],6,$row['GRADE'],'',0,'L',$this->fill);
    //$this->Cell($this->width[5],6,$row['ENTER_CODE'],'',0,'L',$this->fill);
    //$this->Cell($this->width[5],6,'','LRTB',0,'L',$this->fill);
    //$this->Cell($this->width[6],6,$row['concentrations'],'',0,'L',$this->fill);
    $this->Ln(5);
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}