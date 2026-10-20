<?php

namespace App\Domain\Permission;

use App\Domain\Shared\Guard;
use DateTimeImmutable;

/**
 * Permission aggregate: an action that can be granted to roles.
 */
final class Permission
{
  private const DESCRIPTION_MAX_LENGTH = 255;

  private function __construct(
    private PermissionId $id,
    private PermissionName $name,
    private string $description,
    private string $module,
    private string $action,
    private DateTimeImmutable $createdAt
  ) {
  }

  public static function define(
    PermissionId $id,
    string $name,
    string $description = '',
    string $module = '',
    string $action = '',
    ?DateTimeImmutable $now = null
  ): self {
    [$name, $description, $module, $action] = self::normalize($name, $description, $module, $action);

    return new self($id, $name, $description, $module, $action, $now ?? new DateTimeImmutable());
  }

  public static function reconstitute(
    PermissionId $id,
    PermissionName $name,
    string $description,
    string $module,
    string $action,
    DateTimeImmutable $createdAt
  ): self {
    return new self($id, $name, trim($description), trim($module), trim($action), $createdAt);
  }

  /**
   * Changes name/description/scope. Name falls back to `module.action` when empty.
   */
  public function redefine(string $name, string $description, string $module, string $action): void
  {
    [$this->name, $this->description, $this->module, $this->action] =
      self::normalize($name, $description, $module, $action);
  }

  public function belongsToModule(string $module): bool
  {
    return $this->module !== '' && strtolower($this->module) === strtolower(trim($module));
  }

  public function id(): PermissionId
  {
    return $this->id;
  }

  public function name(): PermissionName
  {
    return $this->name;
  }

  public function description(): string
  {
    return $this->description;
  }

  public function module(): string
  {
    return $this->module;
  }

  public function action(): string
  {
    return $this->action;
  }

  public function createdAt(): DateTimeImmutable
  {
    return $this->createdAt;
  }

  /**
   * @return array{0:PermissionName,1:string,2:string,3:string}
   */
  private static function normalize(string $name, string $description, string $module, string $action): array
  {
    $module = PermissionName::normalizeSegment($module);
    $action = PermissionName::normalizeSegment($action);

    return [
      PermissionName::fromParts($name, $module, $action),
      Guard::optionalText(
        $description,
        self::DESCRIPTION_MAX_LENGTH,
        'A descrição da permissão deve ter no máximo ' . self::DESCRIPTION_MAX_LENGTH . ' caracteres.'
      ),
      $module,
      $action,
    ];
  }
}
