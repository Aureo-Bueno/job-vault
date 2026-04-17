<?php

namespace App\Application\Exceptions;

use InvalidArgumentException;

/**
 * Raised when command/query message data is invalid.
 */
final class MessageValidationException extends InvalidArgumentException
{
}
