<?php
namespace phpgt\csrf\exception;

class CSRFTokenMissingException extends CSRFException
{

    public function __construct()
    {
        parent::__construct("CSRF Token not found on \$_POST");
    }
}#
