<?php

namespace App\Domain\Exception;

/**
 * Raised when an operation would break a uniqueness rule.
 */
class AlreadyExistsException extends DomainException
{
}
