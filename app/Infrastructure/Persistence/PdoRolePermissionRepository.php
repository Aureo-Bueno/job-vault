<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Permission;
use App\Domain\Model\Role;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use PDO;

class PdoRolePermissionRepository implements RolePermissionRepositoryInterface
{
  private Database $db;

  public function __construct()
  {
    $this->db = new Database('role_permissions');
  }

  /** @return Permission[] */
  public function getPermissionsByRoleId(string $roleId): array
  {
    $result = $this->db->execute(
      'SELECT p.* FROM permissions p
       JOIN role_permissions rp ON p.id = rp.permission_id
       WHERE rp.role_id = ?',
      [$roleId]
    );

    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    $permissions = [];
    foreach ($rows as $row) {
      $permissions[] = new Permission(
        isset($row['id']) ? (string) $row['id'] : null,
        $row['name'] ?? '',
        $row['description'] ?? '',
        $row['module'] ?? '',
        $row['action'] ?? '',
        $row['created_at'] ?? ''
      );
    }

    return $permissions;
  }

  public function roleHasPermission(string $roleId, string $permissionName): bool
  {
    $result = $this->db->execute(
      'SELECT 1 FROM role_permissions rp
       JOIN permissions p ON rp.permission_id = p.id
       WHERE rp.role_id = ? AND p.name = ?',
      [$roleId, $permissionName]
    );

    return (bool) $result->fetch(PDO::FETCH_ASSOC);
  }

  /** @return Role[] */
  public function getRolesByPermissionId(string $permissionId): array
  {
    $result = $this->db->execute(
      'SELECT r.* FROM roles r
       JOIN role_permissions rp ON r.id = rp.role_id
       WHERE rp.permission_id = ?',
      [$permissionId]
    );

    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    $roles = [];
    foreach ($rows as $row) {
      $roles[] = new Role(
        isset($row['id']) ? (string) $row['id'] : null,
        $row['name'] ?? '',
        $row['description'] ?? '',
        $row['created_at'] ?? ''
      );
    }

    return $roles;
  }

  public function assignPermissionToRole(string $roleId, string $permissionId): bool
  {
    $result = $this->db->execute(
      'SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?',
      [$roleId, $permissionId]
    );

    if ($result->fetch(PDO::FETCH_ASSOC)) {
      return false;
    }

    $this->db->insert([
      'role_id' => $roleId,
      'permission_id' => $permissionId
    ]);

    return true;
  }

  public function removePermissionFromRole(string $roleId, string $permissionId): bool
  {
    $this->db->execute(
      'DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?',
      [$roleId, $permissionId]
    );
    return true;
  }
}
