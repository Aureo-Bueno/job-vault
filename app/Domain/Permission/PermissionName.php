<?php

namespace App\Domain\Permission;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Shared\Guard;

/**
 * Unique permission key, conventionally `module.action` (e.g. `user.create`).
 */
final class PermissionName implements \Stringable
{
  private const MAX_LENGTH = 100;
  private const SEGMENT_MAX_LENGTH = 50;

  private function __construct(private string $value)
  {
  }

  public static function fromString(string $value): self
  {
    return new self(Guard::requiredText(
      $value,
      self::MAX_LENGTH,
      'O nome da permissão é obrigatório.',
      'O nome da permissão deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.'
    ));
  }

  /**
   * Uses the explicit name when given, otherwise composes `module.action`.
   */
  public static function fromParts(string $name, string $module, string $action): self
  {
    if (trim($name) !== '') {
      return self::fromString($name);
    }

    $module = self::normalizeSegment($module);
    $action = self::normalizeSegment($action);
    if ($module === '' || $action === '') {
      throw new InvalidValueException('O nome da permissão é obrigatório (ou informe módulo e ação).');
    }

    return self::fromString($module . '.' . $action);
  }

  /**
   * Normalizes module/action tokens: lowercase, only `a-z0-9._-`.
   */
  public static function normalizeSegment(string $value): string
  {
    $normalized = strtolower(trim($value));
    $normalized = preg_replace('/[^a-z0-9._-]+/', '_', $normalized) ?? '';
    $normalized = trim($normalized, '_');

    if (strlen($normalized) > self::SEGMENT_MAX_LENGTH) {
      throw new InvalidValueException('Módulo e ação devem ter no máximo ' . self::SEGMENT_MAX_LENGTH . ' caracteres.');
    }

    return $normalized;
  }

  public function equals(self $other): bool
  {
    return $this->value === $other->value;
  }

  public function value(): string
  {
    return $this->value;
  }

  public function __toString(): string
  {
    return $this->value;
  }
}
