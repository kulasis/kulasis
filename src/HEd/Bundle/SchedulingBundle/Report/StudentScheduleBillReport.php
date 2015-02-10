<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report as BaseReport;

class StudentScheduleBillReport extends BaseReport {
  
  private $width = array(7, 29, 88, 60);
  private $billing_width = array(16, 25, 20, 85, 20, 20);
  
  private $data;

  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    $this->setReportTitle('STUDENT SCHEDULE AND STATEMENT');
    $this->school_name = $this->data['ORGANIZATION_NAME'];
    $this->term_name = $this->data['TERM_ABBREVIATION'];
    parent::Header();
    
  }
  
  public function first_header() {
    
    $middle_initial = substr($this->data['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    
    // Student Information
    // Student Name
    $this->SetY(50);
    $this->SetLeftMargin(20);
    $this->SetFont('Arial', '', 10);
    $middle_initial = substr($this->data['MIDDLE_NAME'], 0, 1);
    if ($middle_initial) $middle_initial = $middle_initial.'.';
    
    if ($this->data['address'] == 'bill') {
    
      if ($this->data['billing_address']['RECIPIENT'] != '') {
        $this->Cell(0,5, $this->data['billing_address']['RECIPIENT'], '', 0,'L');
        $this->Ln(4);
      }
    
    }
    $name_line = '';
    if (isset($this->data['billing_address']['RECIPIENT']) AND $this->data['address'] == 'bill') $name_line = 'Re: ';
    $name_line = $name_line . $this->data['LAST_NAME'].', '.$this->data['FIRST_NAME'].' '.$middle_initial;
    $this->Cell(0,5, $name_line, '', 0,'L');
    $this->Ln(4);

    // Address
    if ($this->data['address'] == 'bill')
      $this->address($this->data['billing_address']['THOROUGHFARE'], $this->data['billing_address']['LOCALITY'], $this->data['billing_address']['ADMINISTRATIVE_AREA'], $this->data['billing_address']['POSTAL_CODE']);
    else {
      if ($this->data['mail_ADDRESS'] != '')
        $this->address($this->data['mail_ADDRESS'], $this->data['mail_CITY'], $this->data['mail_STATE'], $this->data['mail_ZIPCODE']);
      elseif ($this->data['res_ADDRESS'] != '')
        $this->address($this->data['res_ADDRESS'], $this->data['res_CITY'], $this->data['res_STATE'], $this->data['res_ZIPCODE']);
    }
    $this->SetFont('Arial', '', 8);
    // Student Info
    $this->SetLeftMargin(120);
    $this->SetY(50);
    
    $this->Cell(30,5, 'Student ID:', '', 0,'R');
    $this->Cell(30,5, $this->data['PERMANENT_NUMBER'], '', 0, 'L');
    $this->Ln(4);
    $this->Cell(30,5, 'Phone:', '', 0,'R');
    $this->Cell(30,5, $this->data['PHONE_NUMBER'], '', 0, 'L');
    $this->SetLeftMargin(15);
    $this->Ln(25);
    
    $this->SetFont('Arial', 'B', 8);
    $this->Cell(110,0,'Student Schedule',0,0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(3);
    // Column headings
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
      $header = array('#', 'Section ID', 'Course Title', 'Instructor');
      for($i=0;$i<count($header);$i++)
          $this->Cell($this->width[$i],6,$header[$i],1,0,'L');
      $this->Ln();
      $this->Cell($this->width[0], 6, '', 1, 0, 'L');
      $this->Cell(49,6,'Dates',1,0,'L');
      $this->Cell(38,6,'Days',1,0,'L');
      $this->Cell(40,6,'Time',1,0,'L');
      $this->Cell(50,6,'Room',1,0,'L');
      //$this->Cell(array_sum($this->width)-7-80, 6, '', 1, 0, 'L');
      $this->Ln();
    //$this->Line(10, 22.5, 268, 22.5);
  }
  
  public function address($address, $city, $state, $zipcode) {
    $this->Cell(0,5, $address, '', 0,'L');
    $this->Ln(4);
    $this->Cell(0,5, $city.', '.$state.' '.$zipcode, '', 0,'L');
    $this->Ln(4);
  }
  
  public function table_row($row)
  {
    $this->Cell($this->width[0],6,$this->row_count,'',0,'C',$this->fill);
    $this->Cell($this->width[1],6,$row['SECTION_NUMBER'],'',0,'L',$this->fill);
    $this->Cell($this->width[2],6,substr($row['COURSE_TITLE'], 0, 50),'',0,'L',$this->fill);
    if ($row['ABBREVIATED_NAME'])
      $this->Cell($this->width[3],6,$row['ABBREVIATED_NAME'],'',0,'L',$this->fill);
    else
      $this->Cell($this->width[3],6,'TBA','',0,'L',$this->fill);  
    $this->Ln();
    
    $this->Cell($this->width[0], 6, '', '', 0, 'L', $this->fill);
    if ($row['START_DATE'] AND $row['END_DATE']) {
      $this->Cell(49,6,date('M j, Y', strtotime($row['START_DATE'])).' - '.date('M j, Y', strtotime($row['END_DATE'])),'',0,'L',$this->fill);
    } else {
      
    }
    if (isset($row['meetings'])) {
      foreach($row['meetings'] as $meeting) {
        $this->Cell(38,6,$meeting['meets'],'',0,'L',$this->fill);
        $this->Cell(40,6,$meeting['START_TIME']. ' - '. $meeting['END_TIME'],'',0,'L',$this->fill);
        $this->Cell(50,6,$meeting['ROOM'],'',0,'L',$this->fill);
        $this->Ln();
      }
    } else {
      $this->Cell(38,6,'TBA','',0,'L',$this->fill);
      $this->Cell(40,6,'','',0,'L',$this->fill);
      $this->Cell(50,6,'TBA','',0,'L',$this->fill);
      $this->Ln();
    }
    
    if ($row['NO_CLASS_DATES']) {
      $this->Cell($this->width[0],6,'','',0,'C',$this->fill);
      $this->Cell(array_sum($this->width) - $this->width[0],6,'No Class Dates: '.$row['NO_CLASS_DATES'],'',0,'L',$this->fill);
    }
    
    $this->fill = !$this->fill;
  }
  
  public function billing_header() {
    
    $this->Ln(15);
    $this->SetFont('Arial', 'B', 8);
    $this->Cell(110,0,'Statement',0,0,'L');
    $this->SetFont('Arial', '', 8);
    $this->Ln(3);
    
    $this->SetX(14);
    
    // Column headings
    $this->SetDrawColor(0,0,0);
    $this->SetFillColor(200,200,200);
    $this->SetLineWidth(.1);
      $header = array('Date', 'Org', 'Term', 'Description', 'Amount', 'Balance');
      for($i=0;$i<count($header);$i++)
          $this->Cell($this->billing_width[$i],6,$header[$i],$header[$i] == null ? 0 : 1,0,'C', true);
    $this->Ln();
    $this->SetFillColor(245,245,245);
    
  }
  
  public function billing_previous_balances($balances) {
    
    foreach($balances as $balance) {
      //if ($balance['TERM_ID'] != $this->session->get('term_id')) {
      $data_row['TRANSACTION_DATE'] = '';
      $data_row['ORGANIZATION_ABBREVIATION'] = ''; //$balance['ORGANIZATION_ABBREVIATION'];
      $data_row['TERM_ABBREVIATION'] = ''; //$balance['TERM_ABBREVIATION'];
      $data_row['TRANSACTION_DESCRIPTION'] = 'Previous Balance';
      $data_row['AMOUNT'] = $balance['total_amount'];
      
      if ($balance['total_amount'] != 0)
        $this->SetFont('Arial', 'B', 8);
      else
        $this->SetFont('Arial', '', 8);
        
      $this->billing_table_row($data_row, 'Y');
      unset($data_row);
    }
    $this->SetFont('Arial', '', 8);
    //}
  }
  
  public function billing_total_balance() {
    
    $balance_desc = 'Balance Due';
    
    //if ($this->due_date AND $this->balance > 0) {
    //  $balance_desc .= ' by '.$this->due_date;
    //}
    $this->SetX(14);
    $this->SetFont('Arial', 'B', 8);
    $this->SetFillColor(200,200,200);
    $this->Cell($this->billing_width[0],6,'',1,0,'L', true);
    $this->Cell($this->billing_width[1],6,'',1,0,'L', true);
    $this->Cell($this->billing_width[2],6,'',1,0,'L', true);
    $this->Cell($this->billing_width[3],6,$balance_desc,1,0,'R', true);
    $this->Cell($this->billing_width[4],6,'',1,0,'R', true);
    $this->Cell($this->billing_width[5],6,'$ '.number_format(bcdiv($this->balance, 100, 2), 2),1,0,'R', true);
    $this->Ln();
    $this->SetFillColor(245,245,245);
    $this->SetFont('Arial', '', 8);
  }
  
  public function billing_table_row($row, $previous_balances = null) {
    $this->SetX(14);
    $this->balance += intval(bcmul($row['AMOUNT'], 100));
    if ($previous_balances == 'Y')
      $this->Cell($this->billing_width[0],6,'',1,0,'L', $this->fill);
    else
      $this->Cell($this->billing_width[0],6, ($row['TRANSACTION_DATE'] != '' AND $row['POSTED'] == '1') ? date("m/d/Y", strtotime($row['TRANSACTION_DATE'])) : 'Pending',1,0,'L', $this->fill);
    $this->Cell($this->billing_width[1],6,$row['ORGANIZATION_ABBREVIATION'],1,0,'L',$this->fill);
    $this->Cell($this->billing_width[2],6,$row['TERM_ABBREVIATION'],1,0,'L',$this->fill);
    $this->Cell($this->billing_width[3],6,$row['TRANSACTION_DESCRIPTION'],1,0,'L',$this->fill);
    $this->Cell($this->billing_width[4],6,'$ '.number_format($row['AMOUNT'], 2),1,0,'R',$this->fill);
    $this->Cell($this->billing_width[5],6,'$ '.number_format(bcdiv($this->balance, 100, 2), 2),1,0,'R',$this->fill);
    
    $this->Ln();
    $this->fill = !$this->fill;
  }
  
  public function supply_list_row() {
    
    if (count($this->supply_list) > 0) {
      $this->AddPage();
      $this->SetLeftMargin(20);
      $this->SetY(50);
      
      foreach($this->supply_list as $supply_list_row) {
      
        $this->Cell(10,5, 'Section ID:', '', 0,'R');
        $this->Cell(20,5, $supply_list_row['SECTION_NUMBER'], '', 0, 'L');
        $this->Cell(20,5, 'Course Title:', '', 0,'R');
        $this->Cell(60,5, $supply_list_row['COURSE_TITLE'], '', 0, 'L');
        if ($supply_list_row['SUPPLIES_PRICE']) {
          $this->Cell(30,5, 'Supplies Price:', '', 0,'R');
          $this->Cell(30,5, $supply_list_row['SUPPLIES_PRICE'], '', 0, 'L');
        }
        $this->Ln(10);
        $this->SetFont('Arial', 'U', 8);
        $this->Cell(50,5, 'Required Supplies', '', 0,'L');
        $this->SetFont('Arial', '', 8);
        $this->Ln(4);
        $this->MultiCell(array_sum($this->billing_width), 5, $supply_list_row['SUPPLIES_REQUIRED']);
        $this->Ln(4);
        $this->MultiCell(array_sum($this->billing_width), 5, $supply_list_row['SUPPLIES_OPTIONAL']);
        $this->Ln(4);
      }
    }
  }
  
  public function Footer()
  {
    // Position at 1.5 cm from bottom
    $this->SetY(-18);
    
    $this->SetLeftMargin(10);
    $this->SetRightMargin(10);
    $this->SetX(10);
    $this->SetFont('Arial', '', 7);
    $this->MultiCell(0,3,'Oregon College of Art and Craft is an Oregon not for profit corporation, EIN: 93-0391547');
    $this->Ln();
    parent::Footer();
  }
  
}