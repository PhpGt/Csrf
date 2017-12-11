<?php
namespace Gt\Csrf\Exception;

class CsrfTokenMissingException extends CsrfException {
	public function __construct() {
		parent::__construct("CSRF Token not found");
	}
}