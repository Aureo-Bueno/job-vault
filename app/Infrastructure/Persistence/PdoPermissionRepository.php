<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Permission;
use App\Domain\Repository\PermissionRepositoryInterface;
use PDO;

class PdoPermissionRepository implements PermissionRepositoryInterface
{
  private Database $db;

  public function __construct()
  {
    $this->db = new Database('permissions');
  }

  public function findById(string $id): ?Permission
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE id = ?', [$id]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  public function findByName(string $name): ?Permission
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE name = ? LIMIT 1', [$name]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  /** @return Permission[] */
  public function findAll(): array
  {
    $rows = $this->db->select()->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapRow'], $rows);
  }

  /** @return Permission[] */
  public function findByModule(string $module): array
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE module = ?', [$module]);
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapRow'], $rows);
  }

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
