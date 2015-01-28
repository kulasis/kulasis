<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

class StudentTranscriptReport extends \Kula\Component\Report\BaseReport {
	
	private $data;
	
	// Current column
	private $col = 0;
	// Ordinate of column start
	private $y0;
	
	public $minorLabelCalled;
	
	public function __construct($orientation='P', $unit='mm', $size='Letter') {
		parent::__construct($orientation, $unit, $size);

		$this->SetMargins(10, 12);
		$this->SetAutoPageBreak(true, 30);
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function Header()
	{
  	// School Logo
		//$image1 = KULA_ROOT . "/core/images/ocaclogo_vertical.png";

		$middle_initial = substr($this->data['MIDDLE_NAME'], 0, 1);
		if ($middle_initial) $middle_initial = $middle_initial.'.';
		
		// Student Information
		$this->Cell(60,5, 'Student Name', 'LTR', 0, 'L'); // Left Box
		//$this->Image($image1, 97, 10); // Center Logo
		$this->Ln(4);
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(60,5, $this->data['LAST_NAME'].', '.$this->data['FIRST_NAME'] . ' ' . $middle_initial, 'LBR', 0,'L');
		$this->Ln(5);
		$this->SetFont('Arial', '', 8);
		$this->Cell(30,5,'Student ID ','LTR',0,'L');
		$this->Cell(30,5,'Grade ','LTR',0,'L');
		$this->Ln(4);
		$this->SetFont('Arial', '', 10);
		$this->Cell(30,5, $this->data['PERMANENT_NUMBER'],'LBR',0,'L');
		$this->Cell(30,5, $this->data['GRADE'],'LBR',0,'L');
		$this->Ln(5);
		$this->SetFont('Arial', '', 8);
		$this->Cell(30,5,'Gender ','LTR',0,'L');
		$this->Cell(30,5,'Date of Birth ','LTR',0,'L');
		$this->Ln(4);
		$this->SetFont('Arial', '', 10);
		$this->Cell(30,5, $this->data['GENDER'],'LBR',0,'L');
		$this->Cell(30,5, date('m/d', strtotime($this->data['BIRTH_DATE'])),'LBR',0,'L');
		$this->Ln(5);
		$this->SetFont('Arial', '', 8);
		$this->Cell(60,5,'Program / Concentration','LTR',0,'L');
		$this->Ln(4);
		$this->SetFont('Arial', '', 10);
		
		$program = $this->data['DEGREE_NAME'];
		$program .= $this->data['CONCENTRATION_NAME'] != '' ? ' / '.substr($this->data['CONCENTRATION_NAME'], 0, 12) : '';
		
		$this->Cell(60,5, $program,'LBR',0,'L');
		$this->SetFont('Arial', '', 8);
		$this->Ln(10);

		$y_start_ch = $this->GetY();

		$this->SetY(12);
		$this->Cell(0, 5, 'Student Transcript', 0, 0, 'C');
		$this->Ln(10);
		
		$this->SetLeftMargin(70);
		$this->SetX(70);
		if ($this->data['HIGH_SCHOOL_GRADUATION_DATE']) {
			$this->Cell(40,5,'High School Graduation Date: ','',0,'L');
			$this->Cell(10,5,date('m/d/Y', strtotime($this->data['HIGH_SCHOOL_GRADUATION_DATE'])),'',0,'L');
			$this->Ln(4);
		}
		if ($this->data['ORIG_ENTER_DATE']) {
			$this->Cell(40,5,'Original Enter Date: ','',0,'L');
			$this->Cell(10,5,date('m/d/Y', strtotime($this->data['ORIG_ENTER_DATE'])),'',0,'L');
			$this->Ln(4);
		}
		
		$this->SetLeftMargin(147);
		$this->SetX(147);
		$this->SetY(12);
		
		// College Information
		$this->Cell(60,5, 'Oregon College of Art and Craft', '', 0,'R');
		$this->Ln(4);
		$this->Cell(60,5, '8245 Southwest Barnes Road', '', 0,'R');
		$this->Ln(4);
		$this->Cell(60,5, 'Portland, OR 97225', '', 0,'R');
		$this->Ln(4);
		$this->Cell(60,5, 'Phone: 503-297-5544', '', 0,'R');
		$this->Ln(6);
		
		$this->SetFont('Arial', '', 7);
		// Start columns for ch
		$this->SetY($y_start_ch);
    $this->SetLeftMargin(10);
    $this->SetX(10);
		//$this->MultiCell(0, 3, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 1, 'L');
    $this->Cell(20,6,'Crs ID','LTB',0,'L');
  	$this->Cell(60,6,'Course Title','TB',0,'L');
		$this->Cell(5,6,'Mk','TB',0,'L');
		$this->Cell(13,6,'Credit','TB',0,'R');
    $this->Cell(20,6,'Crs ID','TB',0,'L');
  	$this->Cell(60,6,'Course Title','TB',0,'L');
		$this->Cell(5,6,'Mk','TB',0,'L');
		$this->Cell(13,6,'Credit','RTB',0,'R');
		$this->Ln(8);
		
		// Save ordinate
		$this->y0 = $this->GetY();
		$this->SetCol(0);
		
		// Middle column line
		$this->Line(108.09, 53, 108.09, 250);
		// bottom line
		$this->Line(10, 250, 206, 250);
	}
	
	public function degree_row($row) {
		$this->SetFont('Arial', '', 7);
		$this->Cell(25,3,'Degree Awarded: ',0,0,'L');
		$this->Cell(55,3,$row['DEGREE_NAME'],0,0,'L');
		$this->Cell(18,3,date('m/d/Y', strtotime($row['GRADUATION_DATE'])),0,0,'R');
		$this->Ln(3);
	}
	
	public function degree_major_row($row) {
		$this->SetFont('Arial', '', 7);
		if (!$this->minorLabelCalled)
			$this->Cell(25,3,'Majors: ',0,0,'L');
		else {
			$this->minorLabelCalled = true;
			$this->Cell(25,3,'',0,0,'L');
		}
		$this->Cell(55,3,$row['MAJOR_NAME'],0,0,'L');
		$this->Ln(3);
	}
	
	public function degree_minor_row($row) {
		$this->SetFont('Arial', '', 7);
		if (!$this->minorLabelCalled)
			$this->Cell(25,3,'Minors: ',0,0,'L');
		else {
			$this->minorLabelCalled = true;
			$this->Cell(25,3,'',0,0,'L');
		}
		$this->Cell(55,3,$row['MINOR_NAME'],0,0,'L');
		$this->Ln(3);
	}
	
	public function degree_concentration_row($row) {
		$this->SetFont('Arial', '', 7);
		if (!$this->minorLabelCalled)
			$this->Cell(25,3,'Concentrations: ',0,0,'L');
		else {
			$this->minorLabelCalled = true;
			$this->Cell(25,3,'',0,0,'L');
		}
		$this->Cell(55,3,$row['CONCENTRATION_NAME'],0,0,'L');
		$this->Ln(3);
	}

	public function term_table_row($row) {
		$this->SetFont('Arial', '', 7);
		if ($row['NON_ORGANIZATION_NAME']) {
			$this->Cell(63,3,$row['NON_ORGANIZATION_NAME'],0,0,'L');
			if ($row['CALENDAR_MONTH'] || $row['CALENDAR_YEAR']) 
				$this->Cell(15,3,$row['CALENDAR_MONTH'].'/'.$row['CALENDAR_YEAR'],0,0,'R');
			else
				$this->Cell(15,3,'',0,0,'R');	
			$this->Cell(20,3,$row['TERM'],0,0,'R');
		} else {
			$this->Cell(20,3,$row['TERM'],0,0,'L');
			if ($row['CALENDAR_MONTH'] || $row['CALENDAR_YEAR']) 
				$this->Cell(15,3,$row['CALENDAR_MONTH'].'/'.$row['CALENDAR_YEAR'],0,0,'L');
			else
				$this->Cell(15,3,'',0,0,'L');	
			$this->Cell(63,3,$row['ORGANIZATION_NAME'],0,0,'R');
		}
		$this->Ln(3);
		// Comments
		if (isset($this->data['comments'][$row['CALENDAR_YEAR']][$row['CALENDAR_MONTH']][$row['TERM']]))
			$this->MultiCell(98, 3, $this->data['comments'][$row['CALENDAR_YEAR']][$row['CALENDAR_MONTH']][$row['TERM']]);
		// Standings
		if (isset($this->data['standings'][$row['CALENDAR_YEAR']][$row['CALENDAR_MONTH']][$row['TERM']]))
			$this->MultiCell(98, 3, $this->data['standings'][$row['CALENDAR_YEAR']][$row['CALENDAR_MONTH']][$row['TERM']]);
	}
	
	public function ch_table_row($row) {
		$this->SetFont('Arial', '', 7);
		$this->Cell(20,3,$row['COURSE_NUMBER'],0,0,'L');
		$this->Cell(60,3,substr($row['COURSE_TITLE'], 0, 50),0,0,'L');
		$this->Cell(5,3,$row['MARK'],0,0,'L');
		if ($row['CREDITS_EARNED'])
			$this->Cell(13,3,sprintf('%0.2f', round($row['CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    else
			$this->Cell(13,3,'',0,0,'R');
		$this->Ln(3);
	}
	
	public function gpa_table_row($totals) {
		if ($totals) {
		$this->Ln(2);
		// Header
		$this->Cell(15,3,'',0,0,'R');
		$this->Cell(15,3,'ATT',0,0,'R');
		$this->Cell(15,3,'ERN',0,0,'R');
		$this->Cell(15,3,'HRS',0,0,'R');
		$this->Cell(15,3,'PTS',0,0,'R');
		$this->Cell(15,3,'GPA',0,0,'R');
		$this->Ln(3);
		// Term
		$this->Cell(15,3,'TERM:',0,0,'L');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['TERM']['ATT'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['TERM']['ERN'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['TERM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['TERM']['PTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		if ($totals['TERM']['HRS'] > 0)
			$this->Cell(15,3,sprintf('%0.2f', round($totals['TERM']['PTS'] / $totals['TERM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');	
		else
			$this->Cell(15,3,'0.00',0,0,'R');		
		$this->Ln(3);
		// Cum
		$this->Cell(15,3,'CUM:',0,0,'L');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['CUM']['ATT'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['CUM']['ERN'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['CUM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['CUM']['PTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		if ($totals['CUM']['HRS'] > 0)
			$this->Cell(15,3,sprintf('%0.2f', round($totals['CUM']['PTS'] / $totals['CUM']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');	
		else
			$this->Cell(15,3,'0.00',0,0,'R');		
		$this->Ln(3);
		$this->Ln(5); 
		}
	}
	
	public function level_total_gpa_table_row($totals, $level) {
		if ($totals) {

		$this->Ln(2);
		// Header
		$this->add_header(strtoupper($level).' TOTALS');
		$this->Cell(15,3,'' ,0,0,'L');
		$this->Cell(15,3,'ATT',0,0,'R');
		$this->Cell(15,3,'ERN',0,0,'R');
		$this->Cell(15,3,'HRS',0,0,'R');
		$this->Cell(15,3,'PTS',0,0,'R');
		$this->Cell(15,3,'GPA',0,0,'R');
		$this->Ln(3);
		// institution
		$this->Cell(15,3,'INSTITUTION:',0,0,'L');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['ATT'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['ERN'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['PTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		if ($totals['institution']['HRS'] > 0)
			$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['PTS'] / $totals['institution']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');	
		else
			$this->Cell(15,3,'0.00',0,0,'R');		
		$this->Ln(3);
		// transfer
		$this->Cell(15,3,'TRANSFER:',0,0,'L');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['transfer']['ATT'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['transfer']['ERN'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['transfer']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['transfer']['PTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		if ($totals['transfer']['HRS'] > 0)
			$this->Cell(15,3,sprintf('%0.2f', round($totals['transfer']['PTS'] / $totals['transfer']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');	
		else
			$this->Cell(15,3,'0.00',0,0,'R');		
		$this->Ln(3);
		// total
		$this->Cell(15,3,'TOTAL:',0,0,'L');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['ATT'] + $totals['transfer']['ATT'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['ERN'] + $totals['transfer']['ERN'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['HRS'] + $totals['transfer']['HRS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		$this->Cell(15,3,sprintf('%0.2f', round($totals['institution']['PTS'] + $totals['transfer']['PTS'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
		if ($totals['institution']['HRS'] > 0 OR $totals['transfer']['HRS'] > 0)
			$this->Cell(15,3,sprintf('%0.2f', round(($totals['institution']['PTS'] + $totals['transfer']['PTS']) / ($totals['institution']['HRS'] + $totals['transfer']['HRS']), 2, PHP_ROUND_HALF_UP)),0,0,'R');	
		else
			$this->Cell(15,3,'0.00',0,0,'R');		
		$this->Ln(3);
		$this->Ln(5);
		}
	}
	
	public function currentschedule($data, $student_id, $last_level, $student_rows) {
		if (isset($data[$student_id])) {
		foreach($data[$student_id] as $level => $level_row) {
			if ($last_level != '' AND $level == $last_level) {
				
				$this->add_header(strtoupper($level).' COURSES IN PROGRESS');
				foreach($level_row as $org_name => $org_row) {
					foreach($org_row as $term_name => $term_row) {
						
						// Check how far from bottom
						$amount_to_check = $student_rows[$student_id][$level][$org_name][$term_name] * 3 + 3 + 3 + 5 + 2;
						$current_y = $this->GetY();
						if (260 - $current_y < $amount_to_check) {
							$this->Ln(260 - $current_y);
						}
						
						$this->currentschedule_term_table_row(array('TERM_NAME' => $term_name, 'ORGANIZATION_NAME' => $org_name));
						foreach($term_row as $schedule_row) {
							$this->currentschedule_table_row($schedule_row);
						}
						$this->Ln(3);
					}
				}
			} elseif ($last_level == '') {
				
				
				
				$this->add_header(strtoupper($level).' COURSES IN PROGRESS');
				foreach($level_row as $org_name => $org_row) {
					foreach($org_row as $term_name => $term_row) {
						
						// Check how far from bottom
						$amount_to_check = $student_rows[$student_id][$level][$org_name][$term_name] * 3 + 3 + 3 + 3 + 5 + 2;
						$current_y = $this->GetY();
						if (260 - $current_y < $amount_to_check) {
							$this->Ln(260 - $current_y);
						}
						
						$this->currentschedule_term_table_row(array('TERM_NAME' => $term_name, 'ORGANIZATION_NAME' => $org_name));
						foreach($term_row as $schedule_row) {
							$this->currentschedule_table_row($schedule_row);
						}
						$this->Ln(3);
					}
				}
			}
		}
		}
	}
	
	public function currentschedule_term_table_row($row) {
		$this->SetFont('Arial', '', 7);
		$this->Cell(20,3,$row['TERM_NAME'],0,0,'L');
		$this->Cell(15,3,'',0,0,'L');	
		$this->Cell(63,3,$row['ORGANIZATION_NAME'],0,0,'R');
		$this->Ln(3);
	}
	
	public function currentschedule_table_row($row) {
		$this->SetFont('Arial', '', 7);
		$this->Cell(20,3,$row['COURSE_NUMBER'],0,0,'L');
		$this->Cell(60,3,substr($row['COURSE_TITLE'], 0, 50),0,0,'L');
		$this->Cell(5,3,'',0,0,'L');
		if ($row['CREDITS_ATTEMPTED'])
			$this->Cell(13,3,sprintf('%0.2f', round($row['CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP)),0,0,'R');
    else
			$this->Cell(13,3,'',0,0,'R');
		$this->Ln(3);
	}
	
	public function add_header($title) {
		$this->SetFont('Arial', '', 7);
		
		//if ($this->GetX() > 100) {
			$this->Cell(1,5, '','',0,'C');
			$this->Cell(96,5, $title,'TB',0,'C');
			//} else {
			//$this->Cell(97,5, $title,'LTBR',0,'C');
			//}
		$this->Ln(6);
	}
	
	
	public function SetCol($col) {
    // Set position at a given column
		$this->col = $col;
    $x = 10+$col*98;
    $this->SetLeftMargin($x);
    $this->SetX($x);
	}
	
	public function AcceptPageBreak() {
	    // Method accepting or not automatic page break
	    if($this->col<1)
	    {
	        // Go to next column
	        $this->SetCol($this->col+1);
	        // Set ordinate to top
	        $this->SetY($this->y0);
	        // Keep on page
	        return false;
	    }
	    else
	    {
	        // Go back to first column
	        $this->SetCol(0);
	        // Page break
	        return true;
	    }
	}
	
	public function Footer()
	{
  	// Position at 1.5 cm from bottom
  	$this->SetY(-28);
		
		$this->SetLeftMargin(10);
		$this->SetRightMargin(10);
		$this->SetX(10);
		
		$this->MultiCell(0,3,'The Family Educational Rights and Privacy Act of 1974 (as amended) prohibts the release of this information without the student\'s written consent. An official transcript must include the signature of the registrar, printing on watermarked paper, and the embossed seal of the college or university. This document reports academic information only.');
		$this->Ln(5);
  	// Page number
  	$this->Cell(90,4,'Page '.$this->GroupPageNo().' of '.$this->PageGroupAlias(),0,0,'L');
		$this->Cell(85,4,'School Official\'s Signature: _______________________________________',0,0,'L');
		$this->Cell(20,4,'Date: ' . date("m/d/y") ,0,0,'R');
	}
	
	
	
}