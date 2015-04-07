<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class ClassRosterDirectoryReport extends BaseReport {
  
  private $width = array(6, 15, 40, 30, 25, 30, 30);
  
  private $data;

  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    $this->setReportTitle('CLASS ROSTER DIRECTORY');
    $this->school_name = $this->data['ORGANIZATION_NAME'];
    $this->term_name = $this->data['TERM_ABBREVIATION'];
    parent::Header();

    // Class Information
    $this->Cell(35);
    $this->Cell(15,0,'Section ID#: ',0,0,'R');
    $this->Cell(20,0, $this->data['SECTION_NUMBER'],0,0,'L');
    $this->Cell(65,0,'Meetings: ',0,0,'R');
    if (isset($this->data['meetings'][0])) {
      $meeting_line_1 = $this->data['meetings'][0]['meets'].' '.$this->data['meetings'][0]['START_TIME'].' - '.$this->data['meetings'][0]['END_TIME']. '  ' .$this->data['meetings'][0]['ROOM'];
      $this->Cell(20,0, $meeting_line_1,0,0,'L');
    }
    $this->Ln(5);
    $this->Cell(35);
    $this->Cell(15,0, 'Course: ', 0, 0,'R');
    $this->Cell(40,0, $this->data['COURSE_TITLE'], 0, 0,'L');
    $this->Cell(45,0,'',0,0,'R');
    if (isset($this->data['meetings'][1])) {
      $meeting_line_1 = $this->data['meetings'][1]['meets'].' '.$this->data['meetings'][1]['START_TIME'].' - '.$this->data['meetings'][1]['END_TIME']. '  ' .$this->data['meetings'][1]['ROOM'];
      $this->Cell(20,0, $meeting_line_1,0,0,'L');
    }
    $this->Ln(5);
    $this->Cell(35);
    $this->Cell(15,0, 'Instructor: ',0,0,'R');
    $this->Cell(40,0, $this->data['ABBREVIATED_NAME'],0,0,'L');
    $this->Ln(7);
    // Column headings

    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
      $header = array('#', 'Student ID', 'Last Name', 'First Name', 'Grade', 'Enter Code'); // 'Final Grade'
      for($i=0;$i<count($header);$i++)
          $this->Cell($this->width[$i],6,$header[$i],1,0,'L');
      $this->Ln();
    //$this->Line(10, 22.5, 268, 22.5);
  }
  
  public function table_row($row)
  {
    $middle_initial = substr($row['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    
    $this->Cell($this->width[0],6,$this->row_count,'',0,'C',$this->fill);
    $this->Cell($this->width[1],6,$row['PERMANENT_NUMBER'],'',0,'L',$this->fill);
    $this->Cell($this->width[2],6,$row['LAST_NAME'],'',0,'L',$this->fill);
    $this->Cell($this->width[3],6,$row['FIRST_NAME'].' '.$middle_initial,'',0,'L',$this->fill);
    $this->Cell($this->width[4],6,$row['GRADE'],'',0,'L',$this->fill);
    $this->Cell($this->width[5],6,$row['ENTER_CODE'],'',0,'L',$this->fill);
    $this->Ln();
    $this->Cell(70, 4, $row['address']['THOROUGHFARE'],'', 0,'L',$this->fill);
    $this->Cell(25, 4, $row['phone']['PHONE_NUMBER'],'', 0,'L',$this->fill);
    $this->Cell(51, 4, $row['email']['EMAIL_ADDRESS'],'', 0,'L',$this->fill);
    $this->Ln();
    $this->Cell(70, 6, ($row['address']['LOCALITY'] != '') ? $row['address']['LOCALITY'].', '.$row['address']['ADMINISTRATIVE_AREA'].' '.$row['address']['POSTAL_CODE'] : '','', 0,'L',$this->fill);
    $this->Cell(25, 6, '','', 0,'L',$this->fill);
    $this->Cell(51, 6, '','', 0,'L',$this->fill);
    $this->Ln();
    $this->fill = !$this->fill;
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}