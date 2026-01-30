<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Role;
use App\Domain\Repository\RoleRepositoryInterface;
use PDO;

class PdoRoleRepository implements RoleRepositoryInterface
{
  private Database $db;

  public function __construct()
  {
    $this->db = new Database('roles');
  }

  public function findById(string $id): ?Role
  {
    $result = $this->db->execute('SELECT * FROM roles WHERE id = ?', [$id]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  public function findByName(string $name): ?Role
  {
    $result = $this->db->execute('SELECT * FROM roles WHERE name = ? LIMIT 1', [$name]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  /** @return Role[] */
  public function findAll(): array
  {
    $rows = $this->db->select()->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapRow'], $rows);
  }

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
