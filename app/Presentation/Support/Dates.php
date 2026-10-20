<?php

namespace App\Presentation\Support;

use DateTimeImmutable;
use Throwable;

/**
 * Date formatting helpers for views (input: 'Y-m-d H:i:s' strings from DTOs).
 */
final class Dates
{
  public static function date(string $value): string
  {
    return self::format($value, 'd/m/Y');
  }

  public static function dateTime(string $value): string
  {
    return self::format($value, 'd/m/Y H:i');
  }

  /**
   * "hoje", "há 1 dia", "há 5 dias".
   */
  public static function since(string $value, ?DateTimeImmutable $now = null): string
  {
    $date = self::parse($value);
    if ($date === null) {
      return '—';
    }

    $now ??= new DateTimeImmutable();
    $days = (int) $date->setTime(0, 0)->diff($now->setTime(0, 0))->format('%r%a');

    return match (true) {
      $days <= 0 => 'hoje',
      $days === 1 => 'há 1 dia',
      default => "há {$days} dias",
    };
  }

  private static function format(string $value, string $format): string
  {
    return self::parse($value)?->format($format) ?? '—';
  }

  private static function parse(string $value): ?DateTimeImmutable
  {
    if (trim($value) === '') {
      return null;
    }

    try {
      return new DateTimeImmutable($value);
    } catch (Throwable) {
      return null;
    }
  }
}
