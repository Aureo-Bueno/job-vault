<?php

namespace App\Domain\Exception;

/**
 * Raised when an operation is not allowed by the current state of an aggregate.
 */
class BusinessRuleViolationException extends DomainException
{
}
