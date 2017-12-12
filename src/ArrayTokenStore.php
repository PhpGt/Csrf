<?php
namespace Gt\Csrf;

use Gt\Csrf\Exception\CsrfTokenInvalidException;
use Gt\Csrf\Exception\CsrfTokenSpentException;

/**
 * NOTE that if this implementation is going to work across web requests, it must be stored on the
 * session - it has no other way of remembering the tokens!
 */
class ArrayTokenStore extends TokenStore {
	private $store = [];

	public function __construct(int $maxTokens = null) {
		parent::__construct($maxTokens);
	}

	public function saveToken(string $token):void {
		$this->store[$token] = null;
		if(count($this->store) > self::$MAX_TOKENS) {
			array_shift($this->store);
		}
	}

	public function verifyToken(string $token):bool {
		if(!array_key_exists($token, $this->store)) {
			throw new CsrfTokenInvalidException(
				$token
			);
		}
		elseif(!is_null($this->store[$token])) {
			throw new CsrfTokenSpentException(
				$token,
				$this->store[$token]
			);
		}
		else {
			return true;
		}
	}

	public function consumeToken(string $token):void {
		$this->store[$token] = time();
	}
}