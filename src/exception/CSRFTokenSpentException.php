<?php
namespace Gt\Csrf\exception;

/**
 * The token supplied has been used already.
 *
 * @package Gt\Csrf\exception
 */
class CSRFTokenSpentException extends CSRFException
{
    /**
     * CSRFTokenSpentException constructor.
     *
     * @param string $tokenReceived The token that was received but found to
     *                              have been spent already
     * @param string $previousUseTime The time it was previously used.
     */
    public function __construct(string $tokenReceived, int $previousUseTime
    ) {
        parent::__construct(
            "CSRF Token '{$tokenReceived}' previously used at " . date(
                "c", $previousUseTime));
    }
}#
