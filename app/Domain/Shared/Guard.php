<?php

namespace App\Domain\Shared;

use App\Domain\Exception\InvalidValueException;

/**
 * Small assertion helpers shared by value objects.
 */
final class Guard
{
  private function __construct()
  {
  }

  /**
   * Trims the value and ensures it is not empty and within the max length.
   */
  public static function requiredText(string $value, int $maxLength, string $emptyMessage, string $tooLongMessage): string
  {
    $normalized = trim($value);
    if ($normalized === '') {
      throw new InvalidValueException($emptyMessage);
    }

    if (mb_strlen($normalized) > $maxLength) {
      throw new InvalidValueException($tooLongMessage);
    }

    return $normalized;
  }

  /**
   * Trims an optional text and ensures it is within the max length.
   */
  public static function optionalText(string $value, int $maxLength, string $tooLongMessage): string
  {
    $normalized = trim($value);
    if (mb_strlen($normalized) > $maxLength) {
      throw new InvalidValueException($tooLongMessage);
    }

    return $normalized;
  }
}
