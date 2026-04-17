<?php

namespace App\Application\Exceptions;

use RuntimeException;

/**
 * Raised when authentication is required but missing.
 */
final class UnauthorizedException extends RuntimeException
{
}
