<?php

namespace Kula\Core\Bundle\FrameworkBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthenticatedUserListener implements EventSubscriberInterface {
  private $router;
  private $container;

  public function __construct($router, $container) {
    $this->router = $router;
    $this->container = $container;
  }
    
  public static function getSubscribedEvents() {
    return array(
      KernelEvents::REQUEST => array('onKernelRequest', 0),
    );
  }

  public function onKernelRequest(GetResponseEvent $event) {
    /*
    if ($this->container->get('session')->get('user_id') > 0 AND $this->container->get('session')->get('portal') == 'sis' ) {
      $routeName = $this->container->get('request')->get('_route');
      if ($routeName != 'sis_home' AND $routeName == 'top') {
        $event->setResponse(new RedirectResponse($this->router->generate('sis_home')));
      }
    }
    */
  }
}