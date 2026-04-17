<?php

namespace App\Application\Exceptions;

use RuntimeException;

/**
 * Raised when current user is not allowed to run a message.
 */
final class MessageAuthorizationException extends RuntimeException
{
}
