<?php

namespace App\Domain\Role;

use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Permission\PermissionId;
use App\Domain\Shared\Guard;
use DateTimeImmutable;

/**
 * Role aggregate: a named set of granted permissions.
 */
final class Role
{
  private const DESCRIPTION_MAX_LENGTH = 255;

  /** @var array<string,PermissionId> */
  private array $permissionIds = [];

  /**
   * @param PermissionId[] $permissionIds
   */
  private function __construct(
    private RoleId $id,
    private RoleName $name,
    private string $description,
    private DateTimeImmutable $createdAt,
    array $permissionIds
  ) {
    foreach ($permissionIds as $permissionId) {
      $this->permissionIds[$permissionId->value()] = $permissionId;
    }
  }

  public static function create(RoleId $id, RoleName $name, string $description = '', ?DateTimeImmutable $now = null): self
  {
    return new self($id, $name, self::normalizeDescription($description), $now ?? new DateTimeImmutable(), []);
  }

  /**
   * @param PermissionId[] $permissionIds
   */
  public static function reconstitute(
    RoleId $id,
    RoleName $name,
    string $description,
    DateTimeImmutable $createdAt,
    array $permissionIds
  ): self {
    return new self($id, $name, trim($description), $createdAt, $permissionIds);
  }

  /**
   * The built-in admin role keeps its name: other parts of the system rely on it.
   */
  public function rename(RoleName $name): void
  {
    if ($this->isAdmin() && !$name->isAdmin()) {
      throw new BusinessRuleViolationException('A role admin não pode ser renomeada.');
    }

    $this->name = $name;
  }

  public function describe(string $description): void
  {
    $this->description = self::normalizeDescription($description);
  }

  public function grant(PermissionId $permissionId): void
  {
    if ($this->hasPermission($permissionId)) {
      throw new AlreadyExistsException('Permissão já vinculada à role.');
    }

    $this->permissionIds[$permissionId->value()] = $permissionId;
  }

  public function revoke(PermissionId $permissionId): void
  {
    if (!$this->hasPermission($permissionId)) {
      throw new BusinessRuleViolationException('A permissão não está vinculada à role.');
    }

    unset($this->permissionIds[$permissionId->value()]);
  }

  public function hasPermission(PermissionId $permissionId): bool
  {
    return isset($this->permissionIds[$permissionId->value()]);
  }

  /**
   * Guards role removal: admin is permanent and roles in use cannot be removed.
   */
  public function ensureCanBeDeleted(int $assignedUsers): void
  {
    if ($this->isAdmin()) {
      throw new BusinessRuleViolationException('A role admin não pode ser removida.');
    }

    if ($assignedUsers > 0) {
      throw new BusinessRuleViolationException('Remova a role dos usuários antes de excluir.');
    }
  }

  public function isAdmin(): bool
  {
    return $this->name->isAdmin();
  }

  public function id(): RoleId
  {
    return $this->id;
  }

  public function name(): RoleName
  {
    return $this->name;
  }

  public function description(): string
  {
    return $this->description;
  }

  public function createdAt(): DateTimeImmutable
  {
    return $this->createdAt;
  }

  /** @return PermissionId[] */
  public function permissionIds(): array
  {
    return array_values($this->permissionIds);
  }

  private static function normalizeDescription(string $description): string
  {
    return Guard::optionalText(
      $description,
      self::DESCRIPTION_MAX_LENGTH,
      'A descrição da role deve ter no máximo ' . self::DESCRIPTION_MAX_LENGTH . ' caracteres.'
    );
  }
}
