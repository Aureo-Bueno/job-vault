<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Role;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Util\Uuid;
use PDO;

/**
 * PDO implementation for role persistence operations.
 */
class PdoRoleRepository implements RoleRepositoryInterface
{
  private Database $db;

  public function __construct()
  {
    $this->db = new Database('roles');
  }

  /**
   * {@inheritDoc}
   */
  public function findById(string $id): ?Role
  {
    $result = $this->db->execute('SELECT * FROM roles WHERE id = ?', [$id]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  /**
   * {@inheritDoc}
   */
  public function findByName(string $name): ?Role
  {
    $result = $this->db->execute('SELECT * FROM roles WHERE name = ? LIMIT 1', [$name]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  /**
   * {@inheritDoc}
   *
   * @return Role[]
   */
  public function findAll(): array
  {
    $rows = $this->db->select(null, 'name ASC')->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapRow'], $rows);
  }

  /**
   * {@inheritDoc}
   */
  public function create(Role $role): ?string
  {
    try {
      if ($role->id === null) {
        $role->id = Uuid::generateV4();
      }

      $this->db->insert([
        'id' => $role->id,
        'name' => $role->name,
        'description' => $role->description
      ]);

      return $role->id;
    } catch (\Throwable $exception) {
      return null;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function update(Role $role): bool
  {
    if ($role->id === null) {
      return false;
    }

    try {
      $this->db->execute(
        'UPDATE roles SET name = ?, description = ? WHERE id = ?',
        [$role->name, $role->description, $role->id]
      );
      return true;
    } catch (\Throwable $exception) {
      return false;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function delete(string $id): bool
  {
    try {
      $this->db->execute('DELETE FROM roles WHERE id = ?', [$id]);
      return true;
    } catch (\Throwable $exception) {
      return false;
    }
  }

  /**
   * Maps a database row into a Role model instance.
   */
  private function mapRow(array $row): Role
  {
    return new Role(
      isset($row['id']) ? (string) $row['id'] : null,
      $row['name'] ?? '',
      $row['description'] ?? '',
      $row['created_at'] ?? ''
    );
  }
}
