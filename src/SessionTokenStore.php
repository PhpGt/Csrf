<?php
namespace Gt\Csrf;

use Gt\Csrf\Exception\CsrfTokenInvalidException;
use Gt\Csrf\Exception\CsrfTokenSpentException;

class SessionTokenStore extends TokenStore {
	/**
	 * Save a token as valid for later verification.
	 */
	public function saveToken(string $token):void {
		// TODO: Implement saveToken() method.
	}

	/**
	 * Mark a token as "used".
	 */
	public function consumeToken(string $token):void {
		// TODO: Implement consumeToken() method.
	}

	/**
	 * Check that the token is valid (i.e. exists and has not been consumed already).
	 *
	 * @throws CsrfTokenInvalidException The token is invalid (i.e. is not
	 * contained within the store).
	 * @throws CsrfTokenSpentException The token has been consumed already. This
	 * scenario might be handled differently by the web app in case the user
	 * pressed submit twice in quick succession - instructing them
	 * to refresh the page and resubmit their form for example.
	 */
	public function verifyToken(string $token):bool {
		// TODO: Implement verifyToken() method.
	}
}