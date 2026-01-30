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
    } catch (\Exception $e) {
      $this->logger->error('Failed to fetch user by email', [
        'error' => $e->getMessage(),
        'email' => $email
      ]);
      return null;
    }
  }

  public function findById(string $id): ?User
  {
    try {
      $result = $this->db->execute('SELECT * FROM users WHERE id = ?', [$id]);
      $row = $result->fetch(PDO::FETCH_ASSOC);
      return $row ? $this->mapRow($row) : null;
    } catch (\Exception $e) {
      $this->logger->error('Failed to fetch user by id', [
        'error' => $e->getMessage(),
        'user_id' => $id
      ]);
      return null;
    }
  }

  /** @return User[] */
  public function findAll(?string $where = null, ?string $order = null, ?string $limit = null): array
  {
    try {
      $rows = $this->db->select($where, $order, $limit)->fetchAll(PDO::FETCH_ASSOC);
      return array_map([$this, 'mapRow'], $rows);
    } catch (\Exception $e) {
      $this->logger->error('Failed to fetch users', [
        'error' => $e->getMessage(),
        'where' => $where
      ]);
      return [];
    }
  }

  public function count(?string $where = null): int
  {
    try {
      return $this->db->count($where);
    } catch (\Exception $e) {
      $this->logger->error('Failed to count users', [
        'error' => $e->getMessage()
      ]);
      return 0;
    }
  }

  public function create(User $user): ?string
  {
    try {
      if ($user->roleId === null) {
        $user->roleId = $this->getDefaultRoleId();
      }

      if ($user->id === null) {
        $user->id = Uuid::v4();
      }

      $this->db->insert([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'password' => $user->password,
        'role_id' => $user->roleId
      ]);

      return $user->id;
    } catch (\Exception $e) {
      $this->logger->error('Failed to create user', [
        'error' => $e->getMessage(),
        'email' => $user->email
      ]);
      return null;
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
    } catch (\Exception $e) {
      $this->logger->error('Failed to update user', [
        'error' => $e->getMessage(),
        'user_id' => $user->id
      ]);
      return false;
    }
  }

  public function delete(string $id): bool
  {
    try {
      $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);
      return true;
    } catch (\Exception $e) {
      $this->logger->error('Failed to delete user', [
        'error' => $e->getMessage(),
        'user_id' => $id
      ]);
      return false;
    }
  }

  public function getDefaultRoleId(): ?string
  {
    try {
      $dbRoles = new Database('roles');
      $result = $dbRoles->execute("SELECT id FROM roles WHERE name = 'usuario' LIMIT 1");
      $row = $result->fetch(PDO::FETCH_ASSOC);
      return $row ? (string) $row['id'] : null;
    } catch (\Exception $e) {
      $this->logger->error('Failed to get default role', [
        'error' => $e->getMessage()
      ]);
      return null;
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
