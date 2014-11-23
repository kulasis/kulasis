<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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