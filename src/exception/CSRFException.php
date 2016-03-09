<?php
namespace phpgt\csrf\exception;

class CSRFException extends \Exception
{
    public function __construct(string $message, int $code = 403,
                                \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}#
