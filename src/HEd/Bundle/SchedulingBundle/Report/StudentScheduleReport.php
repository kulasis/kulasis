<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class StudentScheduleReport extends BaseReport {
  
  private $width = array(7, 20, 70, 44.5, 20, 15);
  
  private $data;

  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    $this->setReportTitle('STUDENT SCHEDULE');
    $this->school_name = $this->data['ORGANIZATION_NAME'];
    $this->term_name = $this->data['TERM_ABBREVIATION'];
    parent::Header();
    

    $middle_initial = substr($this->data['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    
    // Student Information
    $this->Ln(20);
    $this->SetFont('Arial', 'B', 8);
    $this->Cell(40,0, $this->data['LAST_NAME'].', '.$this->data['FIRST_NAME'] . ' ' . $middle_initial, 0, 0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Cell(100,0,'Student ID: ',0,0,'R');
    $this->Cell(0,0, $this->data['PERMANENT_NUMBER'],0,0,'L');
    $this->Ln(5);
    $this->Cell(30, 0, $this->data['address']['address'], 0, 0, 'L');
    $this->Cell(110,0,'Phone: ',0,0,'R');
    $this->Cell(30, 0, $this->data['PHONE_NUMBER'], 0, 0, 'L');
    $this->Ln(5);
    $this->Cell(30, 0, $this->data['address']['city'].', '.$this->data['address']['state'].' '.$this->data['address']['zipcode'], 0, 0, 'L');
    $this->Ln(20);
    $this->Cell(25,0,'Grade: ',0,0,'R');
    $this->Cell(30,0, $this->data['GRADE'],0,0,'L');
    $this->Ln(5);
    $this->Cell(25,0,'Degree Program: ',0,0,'R');
    $this->Cell(30,0, $this->data['DEGREE_NAME'],0,0,'L');
    $this->Ln(5);
    $this->Cell(25,0,'Advisor: ',0,0,'R');
    $this->Cell(30,0, $this->data['advisor_ABBREVIATED_NAME'],0,0,'L');
    $this->Ln(7);
    
    // Column headings
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
      $header = array('#', 'Section ID', 'Course Title', 'Instructor', 'Mark Scale', 'Credits');
      for($i=0;$i<count($header);$i++)
          $this->Cell($this->width[$i],6,$header[$i],1,0,'L');
      $this->Ln();
      $this->Cell($this->width[0], 6, '', 1, 0, 'L');
      $this->Cell(20,6,'Days',1,0,'L');
      $this->Cell(40,6,'Time',1,0,'L');
      $this->Cell(20,6,'Room',1,0,'L');
      $this->Cell(array_sum($this->width)-7-80, 6, '', 1, 0, 'L');
      $this->Ln();
    //$this->Line(10, 22.5, 268, 22.5);
  }
  
  public function table_row($row)
  {
    $this->Cell($this->width[0],6,$this->row_count,'',0,'C',$this->fill);
    $this->Cell($this->width[1],6,$row['SECTION_NUMBER'],'',0,'L',$this->fill);
    if ($row['SECTION_NAME'] != '') {
      $this->Cell($this->width[2],6,substr($row['SECTION_NAME'], 0, 50),'',0,'L',$this->fill);
    } else {
      $this->Cell($this->width[2],6,substr($row['COURSE_TITLE'], 0, 50),'',0,'L',$this->fill);
    }
    if ($row['ABBREVIATED_NAME'])
      $this->Cell($this->width[3],6,$row['ABBREVIATED_NAME'],'',0,'L',$this->fill);
    else
      $this->Cell($this->width[3],6,'TBA','',0,'L',$this->fill);  
    $this->Cell($this->width[4],6,$row['MARK_SCALE_NAME'],'',0,'L',$this->fill);
    $this->Cell($this->width[5],6,$row['CREDITS_ATTEMPTED'],'',0,'L',$this->fill);
    $this->Ln();
    
    if (isset($row['meetings'])) {
      foreach($row['meetings'] as $meeting) {
        $this->Cell($this->width[0], 6, '', '', 0, 'L', $this->fill);
        $this->Cell(20,6,$meeting['meets'],'',0,'L',$this->fill);
        $this->Cell(40,6,$meeting['START_TIME']. ' - '. $meeting['END_TIME'],'',0,'L',$this->fill);
        $this->Cell(20,6,$meeting['ROOM'],'',0,'L',$this->fill);
        $this->Cell(array_sum($this->width)-7-80, 6, '', '', 0, 'L', $this->fill);
        $this->Ln();
      }
    } else {
      $this->Cell($this->width[0], 6, '', '', 0, 'L', $this->fill);
      $this->Cell(20,6,'TBA','',0,'L',$this->fill);
      $this->Cell(40,6,'','',0,'L',$this->fill);
      $this->Cell(20,6,'TBA','',0,'L',$this->fill);
      $this->Cell(array_sum($this->width)-7-80, 6, '', '', 0, 'L', $this->fill);
      $this->Ln();
    }
    $this->fill = !$this->fill;
  }
  
  public function credit_row($total_credits) {
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
    $this->Cell(array_sum($this->width)-15, 6, 'Total Credits: ', 'T', 0, 'R');
    $this->Cell(15, 6, $total_credits, 1, 0, 'L');
    $this->Ln();
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}