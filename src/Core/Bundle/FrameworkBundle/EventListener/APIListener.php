<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kula\Core\Bundle\FrameworkBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;


class APIListener implements EventSubscriberInterface
{
  public function __construct($database, $session, $api_logger) {
      $this->database = $database;
      $this->session = $session;
      $this->api_logger = $api_logger;
  }

  public static function getSubscribedEvents() {
      return array(
          KernelEvents::RESPONSE => 'logAPICall',
      );
  }

  public function logAPICall(FilterResponseEvent $event) {

    $this->event = $event;

    $this->request = $event->getRequest();
    $this->response = $event->getResponse();

    if ($this->request->headers->get('Authorization')) {
      $this->api_logger->logAPICall($this->request, $this->response, null);
    }
    
    return $event;
  }


}
