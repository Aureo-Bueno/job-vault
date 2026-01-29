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
  public function getPermissionsByRoleId(int $roleId): array
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
        isset($row['id']) ? (int) $row['id'] : null,
        $row['nome'] ?? '',
        $row['descricao'] ?? '',
        $row['modulo'] ?? '',
        $row['acao'] ?? '',
        $row['created_at'] ?? ''
      );
    }

    return $permissions;
  }

  public function roleHasPermission(int $roleId, string $permissionName): bool
  {
    $result = $this->db->execute(
      'SELECT 1 FROM role_permissions rp
       JOIN permissions p ON rp.permission_id = p.id
       WHERE rp.role_id = ? AND p.nome = ?',
      [$roleId, $permissionName]
    );

    return (bool) $result->fetch(PDO::FETCH_ASSOC);
  }

  /** @return Role[] */
  public function getRolesByPermissionId(int $permissionId): array
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
        isset($row['id']) ? (int) $row['id'] : null,
        $row['nome'] ?? '',
        $row['descricao'] ?? '',
        $row['created_at'] ?? ''
      );
    }

    return $roles;
  }

  public function assignPermissionToRole(int $roleId, int $permissionId): bool
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

  public function removePermissionFromRole(int $roleId, int $permissionId): bool
  {
    $this->db->delete('role_id = ' . intval($roleId) . ' AND permission_id = ' . intval($permissionId));
    return true;
  }
}
