<?php
namespace Gt\Csrf;

use Gt\Csrf\Exception\CsrfTokenInvalidException;
use Gt\Csrf\Exception\CsrfTokenMissingException;
use Gt\Csrf\Exception\CsrfTokenSpentException;
use RandomLib\Factory as RandomLibFactory;
use SecurityLib\Strength;

/**
 * Extend this base class to create a store for CSRF tokens. The core functionality of generating
 * the tokens is provided by the base class, but can be overridden.
 */
abstract class TokenStore {
	/**
	 * @var int|null The maximum number of tokens to be retained.
	 */
	public static $MAX_TOKENS = 1000;
	private static $strength = Strength::MEDIUM;
	private static $tokenLength = 32;
	private $tokenGenerator;

	/**
	 * An optional limit of the number of valid tokens the TokenStore will retain may be passed.
	 * If not specified, an unlimited number of tokens will be retained (which is probably
	 * fine unless you have a very, very busy site with long-running sessions).
	 *
	 * @see static::DEFAULT_MAX_TOKENS
	 */
	public function __construct(int $maxTokens = null) {
		if(!is_null($maxTokens)) {
			self::$MAX_TOKENS = $maxTokens;
		}

// TODO: Remove error_reporting when issue #45 is addressed.
		$oldReportingLevel = error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
		$factory = new RandomLibFactory();
		$this->tokenGenerator = $factory->getGenerator(
			new Strength(self::$strength));
// Set error_reporting back to what it was previously.
		error_reporting($oldReportingLevel);
	}

	/**
	 * Specify that tokens of a different length should be generated.
	 *
	 * @see static::DEFAULT_MAX_TOKENS
	 */
	public function setTokenLength(int $newTokenLength):void {
		self::$tokenLength = $newTokenLength;
	}

	/**
	 * Generate a new token. NOTE: This method does NOT store the token.
	 *
	 * @see TokenStore::saveToken() for storing a generated token.
	 */
	public function generateNewToken():string {
		return $this->tokenGenerator->generateString(self::$tokenLength);
	}

	/**
	 * If a $_POST global exists, check that it contains a token and that the token is valid.
	 * The name the token is stored-under is contained in HTMLDocumentProtector::$TOKEN_NAME.
	 *
	 * @throws CsrfTokenMissingException There's a $_POST request present but no
	 * token present
	 * @throws CsrfTokenInvalidException There's a token included on the $_POST,
	 * but its value is invalid.
	 * @throws CsrfTokenSpentException  There's a token included on the
	 * $_POST but it has already been consumed by a previous request.
	 * @see TokenStore::verifyToken().
	 */
	public function processAndVerify():void {
		// expect the token to be present on ALL post requests
		if(!empty($_POST)) {
			if(!isset($_POST[HTMLDocumentProtector::$TOKEN_NAME])) {
				throw new CsrfTokenMissingException();
			}

			$this->verifyToken($_POST[HTMLDocumentProtector::$TOKEN_NAME]);
			$this->consumeToken($_POST[HTMLDocumentProtector::$TOKEN_NAME]);
		}
	}

	/**
	 * Save a token as valid for later verification.
	 */
	abstract public function saveToken(string $token):void;

	/**
	 * Mark a token as "used".
	 */
	abstract public function consumeToken(string $token):void;

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
	abstract public function verifyToken(string $token):bool;
}
