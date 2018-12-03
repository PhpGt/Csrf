<?php
namespace Gt\Csrf;

use Gt\Csrf\Exception\CsrfTokenInvalidException;
use Gt\Csrf\Exception\CsrfTokenSpentException;
use Gt\Session\SessionStore;

class SessionTokenStore extends TokenStore {
	const SESSION_KEY = "tokenList";

	/** @var SessionStore */
	protected $session;

	public function __construct(SessionStore $session, int $maxTokens = null) {
		$this->session = $session;
		parent::__construct($maxTokens);
	}

	public function saveToken(string $token):void {
		$tokenList = $this->session->get(self::SESSION_KEY) ?? [];
		$tokenList[$token] = null;

		while(count($tokenList) > $this->getMaxTokens()) {
			array_shift($tokenList);
		}

		$this->session->set(self::SESSION_KEY, $tokenList);
	}

	public function verifyToken(string $token):bool {
		$tokenList = $this->session->get(self::SESSION_KEY) ?? [];

		if(!array_key_exists($token, $tokenList)) {
			throw new CsrfTokenInvalidException(
				$token
			);
		}
		elseif(!is_null($tokenList[$token])) {
			throw new CsrfTokenSpentException(
				$token,
				$tokenList[$token]
			);
		}

		return true;
	}

	public function consumeToken(string $token):void {
		$tokenList = $this->session->get(self::SESSION_KEY) ?? [];
		$tokenList[$token] = time();
		$this->session->set(self::SESSION_KEY, $tokenList);
	}
}