<?php
namespace Gt\Csrf\exception;

/**
 * A base class from which other CSRF exceptions inherit.
 *
 * @package Gt\Csrf\exception
 */
class CSRFException extends \Exception
{
    /**
     * CSRFException constructor.
     *
     * @param string          $message The exception message
     * @param int             $code The exception code.  403 if not specified
     * @param \Exception|null $previous The exception that caused this one.
     */
    public function __construct(string $message, int $code = 403,
                                \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}#
