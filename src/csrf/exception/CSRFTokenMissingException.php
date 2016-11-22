<?php
namespace phpgt\csrf\exception;

/**
 * Indicates that no token was found on the request.
 * @package phpgt\csrf\exception
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
