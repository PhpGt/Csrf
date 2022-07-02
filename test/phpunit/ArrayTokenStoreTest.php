<?php
namespace Gt\Csrf\Test;

use Exception;
use Gt\Csrf\ArrayTokenStore;
use Gt\Csrf\Exception\CsrfTokenInvalidException;
use Gt\Csrf\Exception\CsrfTokenSpentException;
use PHPUnit\Framework\TestCase;

class ArrayTokenStoreTest extends TestCase {
	public function testATokenExists() {
		$sut = new ArrayTokenStore();
		// generate a token
		$token = $sut->generateNewToken();
		$sut->saveToken($token);
		// check it exists
		$exception = null;

		try {
			$sut->verifyToken($token);
		}
		catch(Exception $exception) {}

		self::assertNull($exception);
	}

	// token doesn't exist
	public function testATokenDoesntExist() {
		$sut = new ArrayTokenStore();

		// see if a non-existent token passes
		$this->expectException(CSRFTokenInvalidException::class);
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
		$this->expectException(CSRFTokenSpentException::class);
		$sut->verifyToken($token);
	}

	// ensure the limit to the number of tokens works
	public function testTokenLimit() {
		$sut = new ArrayTokenStore();
		$firstToken = $sut->generateNewToken();
		$sut->saveToken($firstToken);

		$tokens = 1;
		$lastToken = null;
		while($tokens++ <= $sut->getMaxTokens()) {
			$lastToken = $sut->generateNewToken();
			$sut->saveToken($lastToken);
		}

		$sut->verifyToken($lastToken);
		// now we've hit the max, the original token should no longer be valid
		$this->expectException(CSRFTokenInvalidException::class);
		$sut->verifyToken($firstToken);
	}

	public function testChangeTokenLimit() {
		$tokenLimit = 5;
		$sut = new ArrayTokenStore($tokenLimit);

		// check that the new limit has stuck
		$this->assertEquals($tokenLimit, $sut->getMaxTokens());


		$firstToken = $sut->generateNewToken();
		$sut->saveToken($firstToken);

		$tokens = 1;
		$lastToken = null;
		while($tokens++ <= $tokenLimit) {
			$lastToken = $sut->generateNewToken();
			$sut->saveToken($lastToken);
		}

		$sut->verifyToken($lastToken);
		// now we've hit the max, the original token should no longer be valid
		$this->expectException(CSRFTokenInvalidException::class);
		$sut->verifyToken($firstToken);
	}
}
