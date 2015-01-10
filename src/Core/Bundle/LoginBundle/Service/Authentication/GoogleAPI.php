<?php

namespace Kula\Core\Bundle\LoginBundle\Service\Authentication;

class GoogleAPI {

	private $session;
	private $client_id;
	private $client_secret;
	private $request;
	private $router;

	private $client;
	
	private $data;

	public function __construct($request, $router, $session, $client_id, $client_secret) {

		// assign dependencies
		$this->session = $session;
		$this->router = $router;
		$this->request = $request->getCurrentRequest();
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		
		if (isset($this->request->server)) {
			// create google client
			$this->client = new \Google_Client();
			$this->client->setClientId($client_id);
			$this->client->setClientSecret($client_secret);
			$this->client->setScopes('email');
			$this->client->setRedirectUri('https://'. $this->request->server->get('HTTP_HOST') . $this->router->generate('login'));
		}
	}
	
	public function authenticate() {
		
		if ($this->session->get('google_access_token') || $this->request->query->get('code')) {
			if ($this->request->query->get('code')) {
			  $this->client->authenticate($this->request->query->get('code'));
			  $this->session->set('google_access_token', $this->client->getAccessToken());
			}
			$this->client->setAccessToken($this->session->get('google_access_token'));
			$this->data = $this->client->verifyIdToken()->getAttributes();
			return true;
		}
		
	}
	
	public function getAuthURL() {
		return $this->client->createAuthUrl();
	}
	
	public function getEmailAddress() {
		if (isset($this->data['payload']['email']))
			return $this->data['payload']['email'];
	}

}