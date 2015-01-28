<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

class FinalGradesReport extends \Kula\Component\Report\BaseReport {
	
	private $width = array(7, 25, 68, 15, 14, 14, 18, 18);
	private $align = array('C', 'L', 'L', 'L', 'R', 'L', 'R', 'R');
	
	private $data;

	public function setData($data) {
		$this->data = $data;
	}
	
	public function Header()
	{
		$this->setReportTitle('FINAL GRADES REPORT');
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
		$this->Cell(30,0, $this->data['advisor_abbreviated_name'],0,0,'L');
		$this->Ln(7);
		
		// Column headings
    $this->SetDrawColor(0,0,0);
		$this->SetFillColor(200,200,200);
    $this->SetLineWidth(.1);
    $header = array('#', 'Section ID', 'Course Title', 'Level', 'Credits', 'Mark', 'GPA Hours', 'Grade Points');
    	for($i=0;$i<count($header);$i++)
        	$this->Cell($this->width[$i],6,$header[$i],1,0,$this->align[$i], true);
    	$this->Ln();
		$this->SetFillColor(245,245,245);
	}
	
	public function table_row($row)
	{
		$this->Cell($this->width[0],6,$this->row_count,1,0,'C',$this->fill);
		$this->Cell($this->width[1],6,$row['SECTION_NUMBER'],1,0,'L',$this->fill);
    $this->Cell($this->width[2],6,substr($row['COURSE_TITLE'], 0, 50),1,0,'L',$this->fill);
    $this->Cell($this->width[3],6,$row['LEVEL'],1,0,'L',$this->fill);
		$this->Cell($this->width[4],6,sprintf('%0.2f', round($row['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),1,0,'R',$this->fill);
    $this->Cell($this->width[5],6,$row['MARK'],1,0,'L',$this->fill);
		if ($row['GPA_VALUE'] == '')
			$this->Cell($this->width[6],6,'',1,0,'R',$this->fill);
		else
			$this->Cell($this->width[6],6,sprintf('%0.2f', round($row['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),1,0,'R',$this->fill);
		$this->Cell($this->width[7],6,sprintf('%0.2f', round($row['QUALITY_POINTS'], 2, PHP_ROUND_HALF_UP)),1,0,'R',$this->fill);
		$this->Ln();
    
    if ($row['COMMENTS']) {
      $this->Cell($this->width[0],6,'',1,0,'C',$this->fill);
      $width = array_sum($this->width) - $this->width[0];
      $this->MultiCell($width,6,$row['COMMENTS'],1,0,'J',$this->fill);
      $this->Ln();
    }

    $this->fill = !$this->fill;
	}
	
	public function gpa_table_row($totals) {
		if ($totals) {
		$this->Ln(5);
		// Header
		$this->SetFont('Arial', 'U', 8);
		$this->Cell(20,3,'',0,0,'R');
		$this->Cell(30,3,'Attempted Credits',0,0,'R');
		$this->Cell(30,3,'Earned Credits',0,0,'R');
		$this->Cell(30,3,'GPA Hours',0,0,'R');
		$this->Cell(30,3,'Grade Points',0,0,'R');
		$this->Cell(30,3,'GPA',0,0,'R');
		$this->SetFont('Arial', '', 8);
		$this->Ln(5);
    
		foreach ($totals as $level => $level_row) {
      
      /*
      if (!isset($level_row['TERM'])) {
        $level_row = array('TERM' => array('ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0));
      } */
    if (isset($level_row['TERM'])) {
    // Term
		$this->Cell(20,3,'Term ('.$level.'):',0,0,'L');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['TERM']['ATT'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['TERM']['ERN'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['TERM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['TERM']['PTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		if ($level_row['TERM']['HRS'] > 0)
			$this->Cell(30,3,sprintf('%0.2f', round($level_row['TERM']['PTS'] / $level_row['TERM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');	
		else
			$this->Cell(30,3,'0.00',0,0,'R');		
		$this->Ln(5);
    }
    /*
    if (!isset($level_row['CUM'])) {
      $level_row = array('CUM' => array('ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0));
    } */
    if (isset($level_row['CUM'])) {
		// Cum
		$this->Cell(20,3,'Cumulative ('.$level.'):',0,0,'L');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['CUM']['ATT'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['CUM']['ERN'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['CUM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(30,3,sprintf('%0.2f', round($level_row['CUM']['PTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		if ($level_row['CUM']['HRS'] > 0)
			$this->Cell(30,3,sprintf('%0.2f', round($level_row['CUM']['PTS'] / $level_row['CUM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');	
		else
			$this->Cell(30,3,'0.00',0,0,'R');		
		$this->Ln(5); 
		
    }
    
		} // end foreach
		} // end if
	}
	
	public function Footer()
	{
  	$this->AliasNbPages();
  	// Position at 1.5 cm from bottom
  	$this->SetY(-18);
  	
  	if ($this->include_footer_info) {
  	// Page number
		if ($this->GroupPageNo())
  		$this->Cell(0,10,'Page '.$this->GroupPageNo().' of '.$this->PageGroupAlias(),0,0,'L');
		else
			$this->Cell(0,10,'Page '.$this->PageNo().' of {nb}',0,0,'L');
  	// User Generated
  	$this->Cell(0,10, date("m/d/y H:i"),0,0,'R');
  	}
  	// Reset Values
  	$this->row_page_count = 0;
  	$this->fill = false;
	}
	
}