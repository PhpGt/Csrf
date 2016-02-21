<?php
namespace phpgt\csrf;

class ArrayTokenStoreTest extends \PHPUnit_Framework_TestCase {

	public function testATokenExists() {
		$sut = new ArrayTokenStore();
		// generate a token
		$token = $sut->generateNewToken();
		$sut->saveToken($token);
		// check it exists
		$this->assertTrue($sut->verifyToken($token));
	}

	// token doesn't exist
	public function testATokenDoesntExist() {
		$sut = new ArrayTokenStore();

		// see if a non-existent token passes
		$this->expectException(
			"\\phpgt\\csrf\\exception\\CSRFTokenInvalidException");
		$sut->verifyToken("mickey mouse");
	}

	// token exists and has been consumed
	public function testConsumeAToken() {
		$sut = new ArrayTokenStore();

		// generate a token
		$token = $sut->generateNewToken();
		$sut->saveToken($token);

		// now consume it
		$sut->consumeToken($token);

		// and make sure it no longer passes verification
		$this->expectException(
			"\\phpgt\\csrf\\exception\\CSRFTokenSpentException");
		$sut->verifyToken($token);
	}
}