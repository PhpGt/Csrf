<?php
namespace Gt\Csrf;

use Gt\Csrf\Exception\CsrfException;
use PHPUnit\Framework\TestCase;

class TokenStoreTest extends TestCase {
	const ONE_FORM
		= <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Test HTML</title>
</head>
<body>
	<h1>This HTML is for the unit test.</h1>
	<p>Hello</p>
    <form method="POST">
        <input type="text">
        <button type="submit"></button>
    </form>
</body>
HTML;

	// no post request received
	public function testNoPOST() {
		$exception = null;

		try {
			$sut = new ArrayTokenStore();
			$sut->processAndVerify([]);
		}
		catch(CsrfException $exception) {}

		self::assertNull($exception);
	}

	// POST request received but without a token
	public function testNoToken() {
		$post = [];
		$post["doink"] = "binky";
		$sut = new ArrayTokenStore();
		$this->expectException(
			"\\Gt\\Csrf\\exception\\CSRFTokenMissingException");
		$sut->processAndVerify($post);
	}

	// POST request received with token but invalid
	public function testInvalidToken() {
		$post = [];
		$post["doink"] = "binky";
		$post[HTMLDocumentProtector::$TOKEN_NAME] = "12321";
		$sut = new ArrayTokenStore();
		$this->expectException(
			"\\Gt\\Csrf\\exception\\CSRFTokenInvalidException");
		$sut->processAndVerify($post);
	}

	// POST request received with token but invalid
	public function testSpentToken() {
		$tokenStore = new ArrayTokenStore();
		$token = $tokenStore->generateNewToken();
		$tokenStore->saveToken($token);
		$tokenStore->consumeToken($token);

		$post = [];
		$post["doink"] = "binky";
		// add the token as if it were from a previous page
		$post[HTMLDocumentProtector::$TOKEN_NAME] = $token;

		$this->expectException(
			"\\Gt\\Csrf\\exception\\CSRFTokenSpentException");
		$tokenStore->processAndVerify($post);
	}

	// POST request received with token and valid
	public function testValidToken() {
		$tokenStore = new ArrayTokenStore();
		$token = $tokenStore->generateNewToken();
		$tokenStore->saveToken($token);

		$post = [];
		$post["doink"] = "binky";
		// add the token as if it were from a previous page
		$post[HTMLDocumentProtector::$TOKEN_NAME] = $token;

		$exception = null;

		try {
			$tokenStore->processAndVerify($post);
		}
		catch(CsrfException $exception) {}

		self::assertNull($exception);
	}

	// check that repeated calls to the token generator result in unique tokens
	public function testCodesAreUnique() {
		$sut = new ArrayTokenStore();
		$previousTokens = [];

		$iterations = 5;
		for($i = 0; $i < $iterations; $i++) {
			$token = $sut->generateNewToken();
			$this->assertArrayNotHasKey($token, $previousTokens);
			$previousTokens[$token] = null;
		}

		$this->assertEquals($iterations, count($previousTokens));
	}

	public function testTokenLengthChange() {
		$sut = new ArrayTokenStore();
		$sut->setTokenLength(6);

		$token = $sut->generateNewToken();
		$this->assertEquals(6, strlen($token));

		// now make sure the shorter token is successfully stored
		$sut->saveToken($token);

		$exception = null;

		try {
			$sut->verifyToken($token);
		}
		catch(\Exception $exception) {}

		self::assertNull($exception);
	}
}