<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

class StaffScheduleReport extends \Kula\Component\Report\BaseReport {
	
	private $width = array(7, 20, 134.5, 15);
	
	private $data;

	public function setData($data) {
		$this->data = $data;
	}
	
	public function Header()
	{
		$this->setReportTitle('INSTRUCTOR SCHEDULE');
		$this->school_name = $this->data['ORGANIZATION_NAME'];
		$this->term_name = $this->data['TERM_ABBREVIATION'];
		parent::Header();
		

		// Student Information
		$this->Ln(20);
		$this->Cell(25,0,'INSTRUCTOR: ',0,0,'R');
		$this->Cell(30,0, $this->data['ABBREVIATED_NAME'],0,0,'L');
		$this->Ln(7);
		
		// Column headings
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
    	$header = array('#', 'Section ID', 'Course Title', 'Credits');
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
    $this->Cell($this->width[2],6,$row['COURSE_TITLE'],'',0,'L',$this->fill);
    $this->Cell($this->width[3],6,$row['CREDITS'],'',0,'L',$this->fill);
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
		}
    $this->fill = !$this->fill;
	}
	
	public function Footer()
	{
		parent::Footer();
	}
	
}