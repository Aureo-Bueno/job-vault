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

  public function findById(int $id): ?Permission
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE id = ?', [$id]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapRow($row) : null;
  }

  public function findByName(string $nome): ?Permission
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE nome = ? LIMIT 1', [$nome]);
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
  public function findByModule(string $modulo): array
  {
    $result = $this->db->execute('SELECT * FROM permissions WHERE modulo = ?', [$modulo]);
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapRow'], $rows);
  }

  private function mapRow(array $row): Permission
  {
    return new Permission(
      isset($row['id']) ? (int) $row['id'] : null,
      $row['nome'] ?? '',
      $row['descricao'] ?? '',
      $row['modulo'] ?? '',
      $row['acao'] ?? '',
      $row['created_at'] ?? ''
    );
  }
}
