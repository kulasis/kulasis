<?php

namespace Kula\HEd\Bundle\BillingBundle\Report;

use Kula\Core\Bundle\FrameworkBundle\Report\Report;

class BillingConstituentLedgerReport extends Report {
  
  private $width = array(16, 20, 12, 30, 69, 20, 20);
  
  private $data;
  public $balance;
  public $term_balance;
  public $total_term_balance;
  private $before_holds_y;
  
  public $due_date;

  public function __construct($orientation='P', $unit='mm', $size='Letter') {
    parent::__construct($orientation, $unit, $size);

    $this->SetMargins(15, 15);
    $this->SetAutoPageBreak(true, 20);
    $this->include_footer_info = true;
  }

  public function setData($data) {
    $this->data = $data;
  }
  
  public function Header()
  {
    // Page number
    //$this->Cell(0,0,'Page '.$this->GroupPageNo().' of '.$this->PageGroupAlias(),0,0,'L');
    $this->Cell(0,0, $this->reportInstitutionName, '', 0,'L');
    // Report Title
    $this->SetX(15);
    $this->Cell(0,0, 'BILLING LEDGER', 0, 0,'C');
    // Date Generated
    $this->Cell(0,0, $this->data['LAST_NAME'].', '.$this->data['FIRST_NAME'].' ('.$this->data['PERMANENT_NUMBER'].')',0,0,'R');
    // Next Line
    $this->Ln(10);
  
    if ($this->GroupPageNo() == 1)
      $this->first_header();
    
    // Column headings
    $this->SetDrawColor(0,0,0);
    $this->SetFillColor(200,200,200);
    $this->SetLineWidth(.1);
      $header = array('Date', 'Org', 'Term', 'Code', 'Description', 'Amount', 'Balance');
      for($i=0;$i<count($header);$i++)
          $this->Cell($this->width[$i],6,$header[$i],$header[$i] == null ? 0 : 1,0,'C', true);
    $this->Ln();
    $this->SetFillColor(245,245,245);
  }
  
  public function first_header() {
    
    // School Logo
    //$image1 = KULA_ROOT . "/core/images/ocaclogo_vertical.png";
    //$this->Cell(1,0, $this->Image($image1, 15, 20), 0, 0, 'L');
    
    // Student Name
    $y_pos = $this->GetY();
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
      elseif ($this->data['residence_ADDRESS'] != '')
        $this->address($this->data['residence_ADDRESS'], $this->data['residence_CITY'], $this->data['residence_STATE'], $this->data['residence_ZIPCODE']);
    }
    $this->SetFont('Arial', '', 8);
    // Student Info
    $this->SetLeftMargin(120);
    $this->SetY($y_pos);
    
    $this->Cell(30,5, 'Student ID:', '', 0,'R');
    $this->Cell(30,5, $this->data['PERMANENT_NUMBER'], '', 0, 'L');
    $this->Ln(4);
    $this->Cell(30,5, 'Phone:', '', 0,'R');
    $this->Cell(30,5, $this->data['PHONE_NUMBER'], '', 0, 'L');
    $this->Ln(4);
    if (isset($this->data['GRADE'])) {
    $this->Cell(30,5, 'Grade:', '', 0,'R');
    $this->Cell(30,5, $this->data['GRADE'].' / '.$this->data['ENTER_CODE'], '', 0, 'L');
    $this->Ln(4);
    }
    if (isset($this->data['DEGREE_NAME'])) {
    $this->Cell(30,5, 'Degree Program:', '', 0,'R');
    $this->Cell(30,5, $this->data['DEGREE_NAME'], '', 0, 'L');
    $this->Ln(4);
    }
    $this->SetLeftMargin(15);
    $this->Ln(10);
  }
  
  public function address($address, $city, $state, $zipcode) {
    $this->Cell(0,5, $address, '', 0,'L');
    $this->Ln(4);
    $this->Cell(0,5, $city.', '.$state.' '.$zipcode, '', 0,'L');
    $this->Ln(4);
  }
  
  public function previous_balance($previous_balance) {
    
    $balance_desc = 'Previous Balance';
    
    $this->balance += $previous_balance;
    
    $this->SetFillColor(200,200,200);
    $this->Cell($this->width[0],6,'',1,0,'L', true);
    $this->Cell($this->width[1],6,'',1,0,'L', true);
    $this->Cell($this->width[2],6,'',1,0,'L', true);
    $this->Cell($this->width[3],6,'',1,0,'L', true);
    $this->Cell($this->width[4],6,$balance_desc,1,0,'R', true);
    $this->Cell($this->width[5],6,'$ '.number_format(bcdiv($previous_balance, 100, 2), 2),1,0,'R', true);
    $this->Cell($this->width[6],6,'$ '.number_format(bcdiv($this->total_term_balance, 100, 2), 2),1,0,'R', true);
    
    $this->Ln();
    $this->SetFillColor(245,245,245);
    $this->SetFont('Arial', '', 8);
  }
  
  public function total_balance($org, $term) {
    
    $balance_desc = 'Term Balance / Balance Forward';
    
    $this->total_term_balance += $this->balance;
    
    $this->SetFont('Arial', 'B', 8);
    $this->SetFillColor(200,200,200);
    $this->Cell($this->width[0],6,'',1,0,'L', true);
    $this->Cell($this->width[1],6,$org,1,0,'L', true);
    $this->Cell($this->width[2],6,$term,1,0,'L', true);
    $this->Cell($this->width[3],6,'',1,0,'L', true);
    $this->Cell($this->width[4],6,$balance_desc,1,0,'R', true);
    $this->Cell($this->width[5],6,'$ '.number_format(bcdiv($this->term_balance[$org][$term], 100, 2), 2),1,0,'R', true);
    $this->Cell($this->width[6],6,'$ '.number_format(bcdiv($this->total_term_balance, 100, 2), 2),1,0,'R', true);
    
    $this->Ln();
    $this->SetFillColor(245,245,245);
    $this->SetFont('Arial', '', 8);
  }
  
  public function table_row($row) {
    
    $this->balance += intval(bcmul($row['AMOUNT'], 100));
    $this->term_balance[$row['ORGANIZATION_ABBREVIATION']][$row['TERM_ABBREVIATION']] += intval(bcmul($row['AMOUNT'], 100));
    $this->Cell($this->width[0],6, ($row['TRANSACTION_DATE'] != '') ? date("m/d/Y", strtotime($row['TRANSACTION_DATE'])) : '',1,0,'L', $this->fill);
    $this->Cell($this->width[1],6,$row['ORGANIZATION_ABBREVIATION'],1,0,'L',$this->fill);
    $this->Cell($this->width[2],6,$row['TERM_ABBREVIATION'],1,0,'L',$this->fill);
    $this->Cell($this->width[3],6,$row['CODE'],1,0,'L',$this->fill);
    $this->Cell($this->width[4],6,$row['TRANSACTION_DESCRIPTION'],1,0,'L',$this->fill);
    $this->Cell($this->width[5],6,'$ '.number_format($row['AMOUNT'], 2),1,0,'R',$this->fill);
    $this->Cell($this->width[6],6,'$ '.number_format(bcdiv($this->balance, 100, 2), 2),1,0,'R',$this->fill);
    
    $this->Ln();
    $this->fill = !$this->fill;
  }
  
  public function Footer()
  {
    parent::Footer();
  }
  
}