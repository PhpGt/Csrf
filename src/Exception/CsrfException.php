<?php
namespace Gt\Csrf\Exception;

use Exception;
use RuntimeException;

class CsrfException extends RuntimeException {
	public function __construct(
		string $message,
		int $code = 403,
		Exception $previous = null
	) {
		parent::__construct(
			$message,
			$code,
			$previous
		);
	}
}