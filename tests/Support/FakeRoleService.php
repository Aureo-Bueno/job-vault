<?php

namespace Tests\Support;

use App\Application\Service\RoleService;
use App\Domain\Model\Role;
use RuntimeException;

class FakeRoleService extends RoleService
{
  /** @var array<string,bool> */
  private array $permissionMap;
  private bool $admin;
  private bool $manager;

  /**
   * @param string[] $permissions
   */
  public function __construct(array $permissions = [], bool $admin = true, bool $manager = false)
  {
    $this->permissionMap = array_fill_keys($permissions, true);
    $this->admin = $admin;
    $this->manager = $manager;
  }

  public function hasPermission(string $userId, string $permissionName): bool
  {
    return isset($this->permissionMap[$permissionName]);
  }

  public function getPermissionsByRole(string $roleId): array
  {
    return array_keys($this->permissionMap);
  }

  public function getUserRole(string $userId): ?Role
  {
    if ($this->admin) {
      return new Role('1', 'admin', '', '');
    }

    if ($this->manager) {
      return new Role('2', 'gestor', '', '');
    }

    return new Role('3', 'usuario', '', '');
  }

  public function listRoles(): array
  {
    return [
      new Role('1', 'admin', 'Administrador', ''),
      new Role('2', 'gestor', 'Gestor', ''),
      new Role('3', 'usuario', 'Usuário', ''),
    ];
  }

  public function isAdmin(string $userId): bool
  {
    return $this->admin;
  }

  public function isManager(string $userId): bool
  {
    return $this->manager;
  }

  public function requirePermission(string $userId, string $permissionName): void
  {
    if (!$this->hasPermission($userId, $permissionName)) {
      throw new RuntimeException('Forbidden.');
    }
  }
}
