<?php
namespace Gt\Csrf\Test;

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
}