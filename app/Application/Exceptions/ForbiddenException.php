<?php

namespace App\Application\Exceptions;

use RuntimeException;

/**
 * Raised when current user does not have enough privileges.
 */
final class ForbiddenException extends RuntimeException
{
}
