<?php

namespace App\Application\Abstractions;

use RuntimeException;

/**
 * Raised when no handler supports a given command or query.
 */
final class HandlerNotFoundException extends RuntimeException
{
}
