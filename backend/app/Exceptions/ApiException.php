<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Business-rule failure with a Turkish, user-facing message. Rendered as { "error": message }.
 */
class ApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
