<?php

namespace App\Application\Exceptions;

use RuntimeException;

/**
 * Raised when an expected resource does not exist.
 */
final class NotFoundException extends RuntimeException
{
}
