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
}