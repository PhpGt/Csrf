<?php
namespace phpgt\csrf\exception;

class CSRFTokenInvalidException extends CSRFException {

	public function __construct(string $tokenReceived) {
		parent::__construct("CSRF Token '{$tokenReceived}' does not exist");
	}
}#