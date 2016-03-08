<?php
namespace phpgt\csrf\exception;

class CSRFTokenSpentException extends CSRFException
{

    public function __construct(string $tokenReceived, string $previousUseTime
    ) {
        parent::__construct(
            "CSRF Token '{$tokenReceived}' previously used at " . date(
                "c", $previousUseTime));
    }
}#
