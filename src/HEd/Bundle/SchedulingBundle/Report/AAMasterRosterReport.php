<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class AAMasterRosterReport extends BaseReport {
  
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
    $this->setReportTitle('ART ADVENTURES MASTER ROSTER');
    $this->school_name = $this->data['ORGANIZATION_NAME'];
    $this->term_name = $this->data['TERM_ABBREVIATION'];
    parent::Header();
  }
  
  public function table_row($row)
  {
    $middle_initial = substr($row['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    
    $this->SetFont('Arial','B',10);
    $this->Cell(137,8,$row['LAST_NAME'].', '.$row['FIRST_NAME'].' '.$middle_initial.' ('.$row['PERMANENT_NUMBER'].')','',0,'L',false);
    $this->Cell(40,8,($row['SECTION_NAME']) ? $row['SECTION_NAME'] : $row['SHORT_TITLE'].' ('.$row['SECTION_NUMBER'].')','',0,'R',false);
    $this->SetFont('Arial','',8);
    $this->Ln();
    $this->Cell(10,6,'Grade:','',0,'L',false);
    $this->Cell(30,6,$row['GRADE'],'',0,'L',false);
    $this->Cell(10,6,'Age:','',0,'',false);
    $this->Cell(30,6,$row['AGE'],'',0,'',false);
    $this->Cell(15,6,'Shirt Size:','',0,'',false);
    $this->Cell(30,6,$row['SHIRT_SIZE'],'',0,'',false);
    $this->Cell(52, 6, '', '', 0, 'L', false);
    $this->Ln();
    $this->Cell(20,6,'Group:','',0,'L',false);
    $this->Cell(157,6,$row['group'],'',0,'L',false);
    $this->Ln();
    $this->Cell(20,6,'Parents:','',0,'L',false);
    $this->Cell(157,6,$row['parents'],'',0,'L',false);
    $this->Ln();
    $this->Cell(20,6,'Phones:','',0,'L',false);
    $this->Cell(157,6,$row['phones'],'',0,'L',false);
    $this->Ln();
    $this->Cell(20,6,'Email:','',0,'L',false);
    $this->Cell(157,6,$row['email_addresses'],'',0,'L',false);
    $this->Ln();
    $this->Cell(157,6,'Health Info:','',0,'L',false);
    $this->Ln();
    $this->MultiCell(157, 6, trim($row['MEDICAL_NOTES']), '', 'L');
    $this->Ln();
    $this->Cell(157,6,'Notes:','',0,'',false);
    $this->Ln();
    $this->MultiCell(157, 6, trim($row['NOTES']), '', 'L');
  
    $this->Ln();
    $this->Cell(20,6,'Emergency Contacts:','',0,'L',false);
    $this->Ln();
    $this->Cell(10,6,'Order','TLRB',0,'L',false);
    $this->Cell(30,6,'Relationship','TLRB',0,'L',false);
    $this->Cell(10,6,'Driver','TLRB',0,'L',false);
    $this->Cell(50,6,'Name','TLRB',0,'L',false);
    $this->Cell(40,6,'Phone','TLRB',0,'L',false);
    $this->Cell(40,6,'Email','TLRB',0,'L',false);
    $this->Ln();
    if ($row['emergency_contacts'] > 0) {
      
      foreach($row['emergency_contacts'] as $contact) {
        
        $this->Cell(10,6,$contact['SORT'],'TLRB',0,'L',false);
        $this->Cell(30,6,$contact['RELATIONSHIP'],'TLRB',0,'L',false);
        $this->Cell(10,6,($contact['AUTHORIZED_DRIVER'] == 1) ? 'Y' : '','TLRB',0,'L',false);
        $this->Cell(50,6,$contact['EMERGENCY_CONTACT_NAME'],'TLRB',0,'L',false);
        $this->Cell(40,6,$contact['EMERGENCY_CONTACT_PHONE'],'TLRB',0,'L',false);
        $this->Cell(40,6,$contact['EMERGENCY_CONTACT_EMAIL'],'TLRB',0,'L',false);
        $this->Ln();
        
      }
      
    }
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}