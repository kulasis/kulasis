<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

use \setasign\Fpdi;

class CoreBilling1098TStudentReportController extends ReportController {
  
  private $pdf;
  
  public function indexAction() {
    $this->authorize();

    return $this->render('KulaCoreBillingBundle:CoreBilling1098TReport:student.html.twig');
  }
  
  public function generateAction() {

    $this->authorize();
    $kernel = $this->get('kernel');

    // initiate FPDI
    $pdf = new Fpdi\Fpdi();
    // add a page
    $pdf->AddPage();
    // set the source file
    $pdf->setSourceFile($kernel->locateResource("@KulaCoreBillingBundle/ReportSource/Form-1098-T-2017.pdf"));
    // import page 1
    $tplIdx = $pdf->importPage(1);
    // use the imported page and place it at point 10,10 with a width of 100 mm
    $pdf->useImportedPage($tplIdx);

    // now write some text above the imported page
    $pdf->SetFont('Helvetica');
    $pdf->SetTextColor(255, 0, 0);
    $pdf->SetXY(30, 30);
    $pdf->Write(0, 'This is just a simple text');
    
    // Closing line
    return $this->pdfResponse($pdf->Output());
    
  }
  

}