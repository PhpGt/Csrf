<?php
namespace phpgt\csrf;

use phpgt\csrf\exception\CSRFTokenMissingException;
use RandomLib\Factory;
use SecurityLib\Strength;


abstract class TokenStore {
	private static $strength    = Strength::MEDIUM;
	private static $tokenLength = 64;
	private        $tokenGenerator;

	public function __construct() {
		$factory = new Factory();
		$this->tokenGenerator = $factory->getGenerator(
			new Strength(self::$strength));
	}

	public function generateNewToken() : string {
		return $this->tokenGenerator->generateString(
			self::$tokenLength);
	}

	public function processAndVerify() {
		// expect the token to be present on ALL post requests
		if(!empty($_POST)) {
			if(!isset($_POST[ HTMLDocumentProtector::$TOKEN_NAME ])) {
				throw new CSRFTokenMissingException();
			}

			$this->verifyToken($_POST[ HTMLDocumentProtector::$TOKEN_NAME ]);
			$this->consumeToken($_POST[ HTMLDocumentProtector::$TOKEN_NAME ]);
		}
	}

	public abstract function saveToken(string $token);

	public abstract function consumeToken(string $token);

	public abstract function verifyToken(string $token) : bool;
}#