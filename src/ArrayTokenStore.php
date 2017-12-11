<?php
namespace Gt\Csrf;

use Gt\Csrf\Exception\CSRFTokenInvalidException;
use Gt\Csrf\Exception\CSRFTokenSpentException;

// NOTE that if this implementation is going to work across web requests, it
// must be stored on the session - it has no other way of remembering the
// tokens!
class ArrayTokenStore extends TokenStore {
	private $store = [];

	/**
	 * ArrayTokenStore constructor.
	 *
	 * @param int|null $maxTokens In this implementation, once the limit is
	 *                             reached, the oldest token is discarded
	 *                             immediately a new one is created that
	 *                             would take the total over the limit,
	 */
	public function __construct(int $maxTokens = null) {
		parent::__construct($maxTokens);
	}

	public function saveToken(string $token) {
		$this->store[$token] = null;
		if(count($this->store) > self::$MAX_TOKENS) {
			array_shift($this->store);
		}
	}

	public function verifyToken(string $token):bool {
		if(!array_key_exists($token, $this->store)) {
			throw new CSRFTokenInvalidException($token);
		}
		elseif($this->store[$token] !== null) {
			throw new CSRFTokenSpentException($token, $this->store[$token]);
		}
		else {
			return true;
		}
	}

	public function consumeToken(string $token) {
		$this->store[$token] = time();
	}
}