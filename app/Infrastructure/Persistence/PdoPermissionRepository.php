<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionId;
use App\Domain\Permission\PermissionName;
use App\Domain\Permission\PermissionRepositoryInterface;
use App\Domain\Role\RoleId;

final class PdoPermissionRepository implements PermissionRepositoryInterface
{
  private const COLUMNS = 'id, name, description, module, action, created_at';

  public function __construct(private Database $db)
  {
  }

  public function get(PermissionId $id): ?Permission
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM permissions WHERE id = ?', [$id->value()]);
    return $row ? $this->map($row) : null;
  }

  public function findByName(PermissionName $name): ?Permission
  {
    $row = $this->db->fetchOne(
      'SELECT ' . self::COLUMNS . ' FROM permissions WHERE name = ? LIMIT 1',
      [$name->value()]
    );
    return $row ? $this->map($row) : null;
  }

  public function all(): array
  {
    $rows = $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM permissions ORDER BY module ASC, action ASC, name ASC');
    return array_map([$this, 'map'], $rows);
  }

  public function namesGrantedToRole(RoleId $roleId): array
  {
    $rows = $this->db->fetchAll(
      'SELECT p.name FROM permissions p
       JOIN role_permissions rp ON rp.permission_id = p.id
       WHERE rp.role_id = ?',
      [$roleId->value()]
    );

    return array_map(static fn (array $row): string => (string) $row['name'], $rows);
  }

  public function save(Permission $permission): void
  {
    $id = $permission->id()->value();
    $values = [
      $permission->name()->value(),
      $permission->description(),
      $permission->module(),
      $permission->action(),
    ];

    if ($this->db->fetchValue('SELECT 1 FROM permissions WHERE id = ?', [$id])) {
      $this->db->run(
        'UPDATE permissions SET name = ?, description = ?, module = ?, action = ? WHERE id = ?',
        [...$values, $id]
      );
      return;
    }

    $this->db->run(
      'INSERT INTO permissions (name, description, module, action, id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
      [...$values, $id, Sql::fromDate($permission->createdAt())]
    );
  }

  public function remove(PermissionId $id): void
  {
    $this->db->run('DELETE FROM permissions WHERE id = ?', [$id->value()]);
  }

  /**
   * @param array<string,mixed> $row
   */
  private function map(array $row): Permission
  {
    return Permission::reconstitute(
      PermissionId::fromString((string) $row['id']),
      PermissionName::fromString((string) $row['name']),
      (string) ($row['description'] ?? ''),
      (string) ($row['module'] ?? ''),
      (string) ($row['action'] ?? ''),
      Sql::toDate($row['created_at'] ?? null)
    );
  }
}
