<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Report;

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
    $birthdate_unix = strtotime($row['BIRTH_DATE']);
    $age = (date("md", date("U", $birthdate_unix)) > date("md"))
    ? ((date("Y") - date("Y", $birthdate_unix)) - 1)
    : (date("Y") - date("Y", $birthdate_unix));
    $this->Cell(30,6,$age,'',0,'L',false);
    $this->Cell(15,6,'Shirt Size:','',0,'L',false);
    $this->Cell(30,6,$row['SHIRT_SIZE'],'',0,'L',false);
	  $this->Cell(30,6,'Gender / Identified:','',0,'L',false);
    $this->Cell(15,6,$row['GENDER'].' / '.$row['IDENTIFIED_GENDER'],'',0,'L',false);
    $this->Cell(7, 6, '', 'R', 0, 'L', false);
    $this->Ln();
    $this->Cell(20,6,'Parents:','L',0,'L',false);
    $this->Cell(157,6,$row['parents'],'R',0,'L',false);
    $this->Ln();
    $this->Cell(20,6,'Phones:','L',0,'L',false);
    $this->Cell(157,6,$row['phones'],'R',0,'L',false);
    $this->Ln();
    $this->Cell(88.5,6,'Registration Info:','L',0,'L',false);
    $this->Cell(88.5,6,'Notes:','R',0,'L',false);
    $this->Ln();
    $x = $this->GetX();
    $y = $this->GetY();

    $medical_notes = 'Food: '.$row['MED_FOOD_ALLERGIES'].' | Allergies: '.$row['MED_ALLERGIES'].' | Limitations: '.$row['MED_LIMITATIONS'].' | Medications: '.$row['MED_MEDICATIONS'].' | Behavioral: '.$row['MED_BEHAVIORAL'].' | Social/Emotional: '.$row['MED_MEN_EMO_SOC_HEALTH'].' | Comments: '.$row['COMMENTS'];
    
    $notes = $row['MEDICAL_NOTES'].' '.$row['NOTES'];

    $height_of_cell = 0;
    $notes_height = $this->getHeightOfCell($notes, 6);
    $medical_notes_height = $this->getHeightOfCell($medical_notes, 6);
    if ($notes_height > $medical_notes_height) {
      $height_of_cell = $notes_height;
    } else {
      $height_of_cell = $medical_notes_height;
    }
    
    $this->MultiCell(88.5, 6, trim($medical_notes), 'L', 'L');
    $this->SetX($x + 88.5);
    $this->SetLeftMargin($x + 88.5);
    $this->SetY($y);


    if (count($row['forms']) > 0) {
      foreach($row['forms'] as $form) {
        $form_agree = ($form['AGREE'] == 1) ? 'Y' : 'N';
        if ($form['AGREE'] != 1) {
          $this->SetFont('Arial','B',8);
        }
        $this->Cell(88.5, 6, $form['FORM_NAME'].': '.$form_agree, '', 'L');
        if ($form['AGREE'] != 1) {
          $this->SetFont('Arial','',8);
        }
        $this->Ln(3);
        unset($form_agree);
      } 
    }

    $this->MultiCell(88.5, 6, trim($notes), 'R', 'L');
    
    $this->SetLeftMargin($x);
    // Draw closing right line
    $this->SetX($x);
    $this->SetY($y);
    $this->Cell(88.5,$height_of_cell,'','L',0,'L',false);
    $this->Cell(88.5,$height_of_cell,'','R',0,'L',false);
    $this->SetX($x);
    $this->Ln();
    $this->Cell(177,5,'','LBR',0,'L',false);
    $this->Ln();
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}