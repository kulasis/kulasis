<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

class MasterScheduleReport extends \Kula\Component\Report\BaseReport {
	
	private $width = array(20, 50, 40, 10, 30, 20, 15, 15, 15, 15, 15);
	
	private $data;

	public function setData($data) {
		$this->data = $data;
	}
	
	public function Header()
	{
		$this->setReportTitle('MASTER SCHEDULE');
		$this->school_name = $this->data['ORGANIZATION_NAME'];
		$this->term_name = $this->data['TERM_ABBREVIATION'];
		$this->show_logo = false;
		parent::Header();

    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.1);
    	$header = array('Section #', 'Course Title', 'Instructor', 'Days', 'Time', 'Room', 'Max', 'Min', 'Enr', 'Left', 'Wait');
    	for($i=0;$i<count($header);$i++)
        	$this->Cell($this->width[$i],6,$header[$i],1,0,'L');
    	$this->Ln();
		//$this->Line(10, 22.5, 268, 22.5);
	}
	
	public function table_row($row)
	{	
		$this->Cell($this->width[0],6,$row['SECTION_NUMBER'],'',0,'L',$this->fill);
    $this->Cell($this->width[1],6,substr($row['COURSE_TITLE'], 0, 33),'',0,'L',$this->fill);
    $this->Cell($this->width[2],6,$row['ABBREVIATED_NAME'],'',0,'L',$this->fill);
		if (isset($row['meetings'])) {
    	$this->Cell($this->width[3],6,$row['meetings'][0]['meets'],'',0,'L',$this->fill);
    	$this->Cell($this->width[4],6,$row['meetings'][0]['START_TIME'].' - '.$row['meetings'][0]['END_TIME'],'',0,'L',$this->fill);
			$this->Cell($this->width[5],6,$row['meetings'][0]['ROOM'],'',0,'L',$this->fill);
		} else {
    	$this->Cell($this->width[3],6,'','',0,'L',$this->fill);
    	$this->Cell($this->width[4],6,'','',0,'L',$this->fill);
    	$this->Cell($this->width[5],6,'','',0,'L',$this->fill);	
		}
		$this->Cell($this->width[6],6,$row['CAPACITY'],'',0,'L',$this->fill);
		$this->Cell($this->width[7],6,$row['MINIMUM'],'',0,'L',$this->fill);
		$this->Cell($this->width[8],6,$row['ENROLLED_TOTAL'],'',0,'L',$this->fill);
		$this->Cell($this->width[9],6,$row['CAPACITY'] - $row['ENROLLED_TOTAL'],'',0,'L',$this->fill);
		$this->Cell($this->width[10],6,$row['WAIT_LISTED_TOTAL'],'',0,'L',$this->fill);
    $this->Ln();
		
		if (isset($row['meetings'])) {
			$count = 0;
		foreach ($row['meetings'] as $meeting) {
			if ($count > 0) {
			$this->Cell(110,6,'','',0,'L',$this->fill);
	    $this->Cell($this->width[3],6,$meeting['meets'],'',0,'L',$this->fill);
    	$this->Cell($this->width[4],6,$meeting['START_TIME'].' - '.$meeting['END_TIME'],'',0,'L',$this->fill);
			$this->Cell($this->width[5],6,$meeting['ROOM'],'',0,'L',$this->fill);
			$this->Cell(75,6,'','',0,'L',$this->fill);
			$this->Ln();
			}
			$count++;
	  }
		}
		
    $this->fill = !$this->fill;
	}
	
	public function Footer()
	{
		parent::Footer();
	}
	
}