<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Permission;
use App\Domain\Repository\PermissionRepositoryInterface;
use App\Util\Uuid;
use PDO;

/**
 * PDO implementation for permission persistence operations.
 */
class PdoPermissionRepository implements PermissionRepositoryInterface
{
  private Database $db;

  public function __construct()
  {
    $this->db = new Database('permissions');
  }

  /**
   * {@inheritDoc}
   */
  public function findById(string $id): ?Permission
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE id = ?', [$id]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  /**
   * {@inheritDoc}
   */
  public function findByName(string $name): ?Permission
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE name = ? LIMIT 1', [$name]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  /**
   * {@inheritDoc}
   *
   * @return Permission[]
   */
  public function findAll(): array
  {
    $rows = $this->db->select(null, 'module ASC, action ASC, name ASC')->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapRow'], $rows);
  }

  /**
   * {@inheritDoc}
   *
   * @return Permission[]
   */
  public function findByModule(string $module): array
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE module = ? ORDER BY action ASC', [$module]);
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapRow'], $rows);
  }

  /**
   * {@inheritDoc}
   */
  public function create(Permission $permission): ?string
  {
    if ($permission->id === null) {
      $permission->id = Uuid::generateV4();
    }

    $this->db->insert([
      'id' => $permission->id,
      'name' => $permission->name,
      'description' => $permission->description,
      'module' => $permission->module,
      'action' => $permission->action
    ]);

    return $permission->id;
  }

  /**
   * {@inheritDoc}
   */
  public function update(Permission $permission): bool
  {
    if ($permission->id === null) {
      return false;
    }

    $this->db->execute(
      'UPDATE permissions SET name = ?, description = ?, module = ?, action = ? WHERE id = ?',
      [
        $permission->name,
        $permission->description,
        $permission->module,
        $permission->action,
        $permission->id
      ]
    );
    return true;
  }

  /**
   * {@inheritDoc}
   */
  public function delete(string $id): bool
  {
    $this->db->execute('DELETE FROM permissions WHERE id = ?', [$id]);
    return true;
  }

  /**
   * Maps a database row into a Permission model instance.
   */
  private function mapRow(array $row): Permission
  {
    return new Permission(
      isset($row['id']) ? (string) $row['id'] : null,
      $row['name'] ?? '',
      $row['description'] ?? '',
      $row['module'] ?? '',
      $row['action'] ?? '',
      $row['created_at'] ?? ''
    );
  }
}
