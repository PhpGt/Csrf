<?php
namespace Gt\Csrf\Exception;

/**
 * Indicates that no token was found on the request.
 * @package Gt\Csrf\exception
 */
class CSRFTokenMissingException extends CSRFException
{
    /**
     * No parameters are required - the token is just missing!
     */
    public function __construct()
    {
        parent::__construct("CSRF Token not found on \$_POST");
    }
}#
