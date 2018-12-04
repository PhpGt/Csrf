<?php
namespace Gt\Csrf\Test;

use Gt\Csrf\Exception\CsrfTokenInvalidException;
use Gt\Csrf\Exception\CsrfTokenSpentException;
use Gt\Csrf\SessionTokenStore;
use Gt\Session\SessionStore;
use PHPUnit\Framework\TestCase;

class SessionTokenStoreTest extends TestCase {
	public function testSaveTokenWhenEmpty() {
		$tokenToSet = uniqid();

		$session = self::createMock(SessionStore::class);
		$session->method("get")
			->willReturn([]);

		$session->expects($this->once())
			->method("set")
			->with(
				SessionTokenStore::SESSION_KEY,
				[$tokenToSet => null]
			);

		/** @var SessionStore $session */
		$sessionTokenStore = new SessionTokenStore($session);
		$sessionTokenStore->saveToken($tokenToSet);
	}

	public function testSaveTokenWhenNotEmpty() {
		$tokenToSet = uniqid("new-", true);
		$existingTokens = [];
		for($i = 0; $i < 10; $i++) {
			$key = uniqid("existing-", true);
			$existingTokens[$key] = null;
		}

		$session = self::createMock(SessionStore::class);
		$session->method("get")
			->willReturn($existingTokens);

		$existingTokensWithNewToken = [];
		foreach($existingTokens as $key => $value) {
			$existingTokensWithNewToken[$key] = $value;
		}
		$existingTokensWithNewToken[$tokenToSet] = null;

		$session->expects($this->once())
			->method("set")
			->with(
				SessionTokenStore::SESSION_KEY,
				$existingTokensWithNewToken
			);

		/** @var SessionStore $session */
		$sessionTokenStore = new SessionTokenStore($session);
		$sessionTokenStore->saveToken($tokenToSet);
	}

	public function testSaveTokenPastMaxTokens() {
		$tokenToSet = uniqid("new-");
		$existingTokens = [];
		for($i = 0; $i < 10; $i++) {
			$key = uniqid("existing-$i-");
			$existingTokens[$key] = null;
		}

		$session = self::createMock(SessionStore::class);
		$session->method("get")
			->willReturn($existingTokens);

		$existingTokensWithNewToken = [];
		foreach($existingTokens as $key => $value) {
			$existingTokensWithNewToken[$key] = $value;
		}
		array_shift($existingTokensWithNewToken);
		$existingTokensWithNewToken[$tokenToSet] = null;

		$session->expects($this->once())
			->method("set")
			->with(
				SessionTokenStore::SESSION_KEY,
				$existingTokensWithNewToken
			);

		/** @var SessionStore $session */
		$sessionTokenStore = new SessionTokenStore(
			$session,
			10
		);
		$sessionTokenStore->saveToken($tokenToSet);
	}

	public function testVerifyTokenNotExists() {
		$tokenToCheckFor = uniqid("token-");
		$existingTokens = [];
		for($i = 0; $i < 10; $i++) {
			$key = uniqid("token-");
			$existingTokens[$key] = null;
		}

		$session = self::createMock(SessionStore::class);
		$session->method("get")
			->willReturn($existingTokens);

		self::expectException(CsrfTokenInvalidException::class);
		/** @var SessionStore $session*/
		$sessionTokenStore = new SessionTokenStore($session);
		$sessionTokenStore->verifyToken($tokenToCheckFor);
	}

	public function testVerifyTokenSpent() {
		$tokenToCheckFor = uniqid("token-");
		$existingTokens = [];
		for($i = 0; $i < 9; $i++) {
			$key = uniqid("token-");
			$existingTokens[$key] = null;
		}

// Add the token to check for in the middle of the existing tokens.
		$existingTokens = array_merge(
			array_slice($existingTokens, 0, 4),
			[$tokenToCheckFor => time()],
			array_slice($existingTokens, 4)
		);

		$session = self::createMock(SessionStore::class);
		$session->method("get")
			->willReturn($existingTokens);

		self::expectException(CsrfTokenSpentException::class);
		/** @var SessionStore $session */
		$sessionTokenStore = new SessionTokenStore($session);
		$sessionTokenStore->verifyToken($tokenToCheckFor);
	}

	public function testVerify() {
		$tokenToCheckFor = uniqid("token-");
		$existingTokens = [];
		for($i = 0; $i < 9; $i++) {
			$key = uniqid("token-");
			$value = rand(0, 1) ? null : time();
			$existingTokens[$key] = $value;
		}

		$existingTokens = array_merge(
			array_slice($existingTokens, 0, 4),
			[$tokenToCheckFor => null],
			array_slice($existingTokens, 4)
		);

		$session = self::createMock(SessionStore::class);
		$session->method("get")
			->willReturn($existingTokens);

		$exception = null;
		/** @var SessionStore $session*/
		$sesssionTokenStore = new SessionTokenStore($session);

		try {
			$sesssionTokenStore->verifyToken($tokenToCheckFor);
		}
		catch(\Exception $exception) {}

		self::assertNull($exception);
	}
}