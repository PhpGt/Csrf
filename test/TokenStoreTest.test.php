<?php
namespace phpgt\csrf;

// NOTE - only test the non-abstract functionality
class TokenStoreTest extends \PHPUnit_Framework_TestCase {
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
		$sut = new ArrayTokenStore();
		$sut->processAndVerify();
	}

	// POST request received but without a token
	public function testNoToken() {
		$_POST["doink"] = "binky";
		$sut = new ArrayTokenStore();
		$this->expectException(
			"\\phpgt\\csrf\\exception\\CSRFTokenMissingException");
		$sut->processAndVerify();
	}

	// POST request received with token but invalid
	public function testInvalidToken() {
		$_POST["doink"] = "binky";
		$_POST[ HTMLDocumentProtector::$TOKEN_NAME ] = "12321";
		$sut = new ArrayTokenStore();
		$this->expectException(
			"\\phpgt\\csrf\\exception\\CSRFTokenInvalidException");
		$sut->processAndVerify();
	}

	// POST request received with token but invalid
	public function testSpentToken() {
		$tokenStore = new ArrayTokenStore();
		$token = $tokenStore->generateNewToken();
		$tokenStore->saveToken($token);
		$tokenStore->consumeToken($token);

		$_POST["doink"] = "binky";
		// add the token as if it were from a previous page
		$_POST[ HTMLDocumentProtector::$TOKEN_NAME ] = $token;

		$this->expectException(
			"\\phpgt\\csrf\\exception\\CSRFTokenSpentException");
		$tokenStore->processAndVerify();
	}

	// POST request received with token and valid
	public function testValidToken() {
		$tokenStore = new ArrayTokenStore();
		$token = $tokenStore->generateNewToken();
		$tokenStore->saveToken($token);

		$_POST["doink"] = "binky";
		// add the token as if it were from a previous page
		$_POST[ HTMLDocumentProtector::$TOKEN_NAME ] = $token;

		$tokenStore->processAndVerify();
	}

	// check that repeated calls to the token generator result in unique tokens
	public function testCodesAreUnique() {
		$sut = new ArrayTokenStore();
		$previousTokens = [ ];

		$iterations = 5;
		for($i = 0; $i < $iterations; $i++) {
			$token = $sut->generateNewToken();
			$this->assertArrayNotHasKey($token, $previousTokens);
			$previousTokens[ $token ] = null;
		}

		$this->assertEquals($iterations, count($previousTokens));
	}
}