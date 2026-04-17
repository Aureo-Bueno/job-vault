<?php

namespace App\Application\Exceptions;

use RuntimeException;

/**
 * Raised when an operation fails for non-validation reasons.
 */
final class OperationFailedException extends RuntimeException
{
}
