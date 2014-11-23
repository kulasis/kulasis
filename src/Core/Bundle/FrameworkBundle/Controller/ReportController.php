<?php

/**
 * @author Makoa Jacobsen <makoa@makoajacobsen.com>
 * @copyright Copyright (c) 2014, Oregon College of Art & Craft
 * @license MIT
 *
 * @package Kula SIS
 * @subpackage Core
 *
 * Base controller that includes all Kula SIS\Core functionality. May be extended
 * further to include additional functionality.
 */

namespace Kula\Bundle\Core\KulaFrameworkBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends BaseController {

	public function pdfResponse($pdf) {
		
		$response = new Response($pdf, 200, array(
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'inline; filename=""',
			'Cache-Control' => 'private, max-age=0, must-revalidate',
			'Pragma' => 'public',
		));
		return $response;
		
	}
	
}