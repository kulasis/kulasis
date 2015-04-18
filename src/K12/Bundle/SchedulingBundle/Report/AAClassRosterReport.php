<?php

namespace Kula\K12\Bundle\SchedulingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class AAClassRosterReport extends BaseReport {
  
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
    $this->setReportTitle('ART ADVENTURES CLASS ROSTER');
    $this->school_name = $this->data['ORGANIZATION_NAME'];
    $this->term_name = $this->data['TERM_ABBREVIATION'];
    parent::Header();
  }
  
  public function table_row($row)
  {
    $middle_initial = substr($row['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    
    $this->SetFont('Arial','B',10);
    $this->Cell(137,8,$row['LAST_NAME'].', '.$row['FIRST_NAME'].' '.$middle_initial.' ('.$row['PERMANENT_NUMBER'].')','TL',0,'L',true);
    $this->Cell(40,8,($row['SECTION_NAME']) ? $row['SECTION_NAME'] : $row['SHORT_TITLE'].' ('.$row['SECTION_NUMBER'].')','TR',0,'R',true);
    $this->SetFont('Arial','',8);
    $this->Ln();
    $this->Cell(10,6,'Grade:','L',0,'L',false);
    $this->Cell(30,6,$row['GRADE'],'',0,'L',false);
    $this->Cell(10,6,'Age:','',0,'L',false);
    $this->Cell(30,6,$row['AGE'],'',0,'L',false);
    $this->Cell(15,6,'Shirt Size:','',0,'L',false);
    $this->Cell(30,6,$row['SHIRT_SIZE'],'',0,'L',false);
    $this->Cell(52, 6, '', 'R', 0, 'L', false);
    $this->Ln();
    $this->Cell(20,6,'Parents:','L',0,'L',false);
    $this->Cell(157,6,$row['parents'],'R',0,'L',false);
    $this->Ln();
    $this->Cell(20,6,'Phones:','L',0,'L',false);
    $this->Cell(157,6,$row['phones'],'R',0,'L',false);
    $this->Ln();
    $this->Cell(88.5,6,'Health Info:','L',0,'L',false);
    $this->Cell(88.5,6,'Notes:','R',0,'L',false);
    $this->Ln();
    $x = $this->GetX();
    $y = $this->GetY();
    $this->MultiCell(88.5, 6, trim($row['MEDICAL_NOTES']), 'L', 'L');
    //$this->SetX($x);
    //$this->SetY($y);
    //$this->MultiCell(88.5, 6, trim($row['NOTES']), 'R', 'L');
    //$this->SetX($x);
    $this->Cell(177,2,'','LBR',0,'L',false);
    $this->Ln();
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}