<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Util\Logger;
use App\Util\Uuid;
use PDO;

class PdoUserRepository implements UserRepositoryInterface
{
  private Database $db;
  private Logger $logger;

  public function __construct()
  {
    $this->db = new Database('users');
    $this->logger = new Logger('user');
  }

  public function findByEmail(string $email): ?User
  {
    try {
      $result = $this->db->execute('SELECT * FROM users WHERE email = ?', [$email]);
      $row = $result->fetch(PDO::FETCH_ASSOC);
      return $row ? $this->mapRow($row) : null;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to fetch user by email', [
        'error' => $e->getMessage(),
        'email' => $email
      ]);
      throw $e;
    }
  }

  public function findById(string $id): ?User
  {
    try {
      $result = $this->db->execute('SELECT * FROM users WHERE id = ?', [$id]);
      $row = $result->fetch(PDO::FETCH_ASSOC);
      return $row ? $this->mapRow($row) : null;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to fetch user by id', [
        'error' => $e->getMessage(),
        'user_id' => $id
      ]);
      throw $e;
    }
  }

  /** @return User[] */
  public function findAll(
    ?string $where = null,
    ?string $order = null,
    ?string $limit = null,
    array $params = []
  ): array
  {
    try {
      $rows = $this->db->select($where, $order, $limit, '*', $params)->fetchAll(PDO::FETCH_ASSOC);
      return array_map([$this, 'mapRow'], $rows);
    } catch (\Throwable $e) {
      $this->logger->error('Failed to fetch users', [
        'error' => $e->getMessage(),
        'where' => $where
      ]);
      throw $e;
    }
  }

  public function count(?string $where = null, array $params = []): int
  {
    try {
      return $this->db->count($where, $params);
    } catch (\Throwable $e) {
      $this->logger->error('Failed to count users', [
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  public function create(User $user): ?string
  {
    try {
      if ($user->roleId === null) {
        $user->roleId = $this->getDefaultRoleId();
      }

      if ($user->id === null) {
        $user->id = Uuid::generateV4();
      }

      $this->db->insert([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'password' => $user->password,
        'role_id' => $user->roleId
      ]);

      return $user->id;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to create user', [
        'error' => $e->getMessage(),
        'email' => $user->email
      ]);
      throw $e;
    }
  }

  public function update(User $user): bool
  {
    try {
      $values = [
        'name' => $user->name,
        'email' => $user->email,
        'role_id' => $user->roleId
      ];

      if (!empty($user->password)) {
        $values['password'] = $user->password;
      }

      $setClause = implode(' = ?, ', array_keys($values)) . ' = ?';
      $params = array_values($values);
      $params[] = $user->id;
      $this->db->execute('UPDATE users SET ' . $setClause . ' WHERE id = ?', $params);
      return true;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to update user', [
        'error' => $e->getMessage(),
        'user_id' => $user->id
      ]);
      throw $e;
    }
  }

  public function delete(string $id): bool
  {
    try {
      $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);
      return true;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to delete user', [
        'error' => $e->getMessage(),
        'user_id' => $id
      ]);
      throw $e;
    }
  }

  public function getDefaultRoleId(): ?string
  {
    try {
      $dbRoles = new Database('roles');
      $result = $dbRoles->execute("SELECT id FROM roles WHERE name = 'usuario' LIMIT 1");
      $row = $result->fetch(PDO::FETCH_ASSOC);
      return $row ? (string) $row['id'] : null;
    } catch (\Throwable $e) {
      $this->logger->error('Failed to get default role', [
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  private function mapRow(array $row): User
  {
    return new User(
      isset($row['id']) ? (string) $row['id'] : null,
      $row['name'] ?? '',
      $row['email'] ?? '',
      $row['password'] ?? '',
      isset($row['role_id']) ? (string) $row['role_id'] : null
    );
  }
}
