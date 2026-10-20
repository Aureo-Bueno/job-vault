<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Permission\PermissionId;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\Role\RoleRepositoryInterface;

/**
 * Persists the Role aggregate: `roles` row + its `role_permissions` grants.
 */
final class PdoRoleRepository implements RoleRepositoryInterface
{
  private const COLUMNS = 'id, name, description, created_at';

  public function __construct(private Database $db)
  {
  }

  public function get(RoleId $id): ?Role
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM roles WHERE id = ?', [$id->value()]);
    return $row ? $this->map($row, $this->grantsOf($id->value())) : null;
  }

  public function findByName(RoleName $name): ?Role
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM roles WHERE name = ? LIMIT 1', [$name->value()]);
    return $row ? $this->map($row, $this->grantsOf((string) $row['id'])) : null;
  }

  public function all(): array
  {
    $grants = [];
    foreach ($this->db->fetchAll('SELECT role_id, permission_id FROM role_permissions') as $grant) {
      $grants[(string) $grant['role_id']][] = (string) $grant['permission_id'];
    }

    $rows = $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM roles ORDER BY name ASC');
    return array_map(fn (array $row): Role => $this->map($row, $grants[(string) $row['id']] ?? []), $rows);
  }

  public function save(Role $role): void
  {
    $id = $role->id()->value();
    $exists = $this->db->fetchValue('SELECT 1 FROM roles WHERE id = ?', [$id]);

    if ($exists) {
      $this->db->run('UPDATE roles SET name = ?, description = ? WHERE id = ?', [
        $role->name()->value(),
        $role->description(),
        $id,
      ]);
    } else {
      $this->db->run('INSERT INTO roles (id, name, description, created_at) VALUES (?, ?, ?, ?)', [
        $id,
        $role->name()->value(),
        $role->description(),
        Sql::fromDate($role->createdAt()),
      ]);
    }

    $this->syncGrants($id, array_map(static fn (PermissionId $p): string => $p->value(), $role->permissionIds()));
  }

  public function remove(RoleId $id): void
  {
    $this->db->run('DELETE FROM roles WHERE id = ?', [$id->value()]);
  }

  /**
   * @param string[] $wanted
   */
  private function syncGrants(string $roleId, array $wanted): void
  {
    $current = $this->grantsOf($roleId);

    foreach (array_diff($current, $wanted) as $permissionId) {
      $this->db->run('DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?', [$roleId, $permissionId]);
    }

    foreach (array_diff($wanted, $current) as $permissionId) {
      $this->db->run('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleId, $permissionId]);
    }
  }

  /** @return string[] */
  private function grantsOf(string $roleId): array
  {
    $rows = $this->db->fetchAll('SELECT permission_id FROM role_permissions WHERE role_id = ?', [$roleId]);
    return array_map(static fn (array $row): string => (string) $row['permission_id'], $rows);
  }

  /**
   * @param array<string,mixed> $row
   * @param string[] $permissionIds
   */
  private function map(array $row, array $permissionIds): Role
  {
    return Role::reconstitute(
      RoleId::fromString((string) $row['id']),
      RoleName::fromString((string) $row['name']),
      (string) ($row['description'] ?? ''),
      Sql::toDate($row['created_at'] ?? null),
      array_map([PermissionId::class, 'fromString'], $permissionIds)
    );
  }
}
