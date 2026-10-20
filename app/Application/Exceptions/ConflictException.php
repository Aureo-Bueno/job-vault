<?php

namespace App\Application\Exceptions;

use RuntimeException;

/**
 * Raised when the request was based on stale data (e.g. someone else already moved the item).
 */
final class ConflictException extends RuntimeException
{
}
