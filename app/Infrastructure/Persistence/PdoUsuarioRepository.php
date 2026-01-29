<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Model\Usuario;
use App\Domain\Repository\UsuarioRepositoryInterface;
use App\Util\Logger;
use PDO;

class PdoUsuarioRepository implements UsuarioRepositoryInterface
{
  private Database $db;
  private Logger $logger;

  public function __construct()
  {
    $this->db = new Database('usuarios');
    $this->logger = new Logger('usuario');
  }

  public function findByEmail(string $email): ?Usuario
  {
    try {
      $result = $this->db->execute('SELECT * FROM usuarios WHERE email = ?', [$email]);
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

  public function findById(int $id): ?Usuario
  {
    try {
      $result = $this->db->execute('SELECT * FROM usuarios WHERE id = ?', [$id]);
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

  /** @return Usuario[] */
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

  public function create(Usuario $usuario): ?int
  {
    try {
      if ($usuario->roleId === null) {
        $usuario->roleId = $this->getDefaultRoleId();
      }

      $id = $this->db->insert([
        'nome' => $usuario->nome,
        'email' => $usuario->email,
        'senha' => $usuario->senha,
        'role_id' => $usuario->roleId
      ]);

      $usuario->id = (int) $id;
      return $usuario->id;
    } catch (\Exception $e) {
      $this->logger->error('Failed to create user', [
        'error' => $e->getMessage(),
        'email' => $usuario->email
      ]);
      return null;
    }
  }

  public function update(Usuario $usuario): bool
  {
    try {
      $values = [
        'nome' => $usuario->nome,
        'email' => $usuario->email,
        'role_id' => $usuario->roleId
      ];

      if (!empty($usuario->senha)) {
        $values['senha'] = $usuario->senha;
      }

      $this->db->update('id = ' . intval($usuario->id), $values);
      return true;
    } catch (\Exception $e) {
      $this->logger->error('Failed to update user', [
        'error' => $e->getMessage(),
        'user_id' => $usuario->id
      ]);
      return false;
    }
  }

  public function delete(int $id): bool
  {
    try {
      $this->db->delete('id = ' . intval($id));
      return true;
    } catch (\Exception $e) {
      $this->logger->error('Failed to delete user', [
        'error' => $e->getMessage(),
        'user_id' => $id
      ]);
      return false;
    }
  }

  public function getDefaultRoleId(): ?int
  {
    try {
      $dbRoles = new Database('roles');
      $result = $dbRoles->execute("SELECT id FROM roles WHERE nome = 'usuario' LIMIT 1");
      $row = $result->fetch(PDO::FETCH_ASSOC);
      return $row ? (int) $row['id'] : null;
    } catch (\Exception $e) {
      $this->logger->error('Failed to get default role', [
        'error' => $e->getMessage()
      ]);
      return null;
    }
  }

  private function mapRow(array $row): Usuario
  {
    return new Usuario(
      isset($row['id']) ? (int) $row['id'] : null,
      $row['nome'] ?? '',
      $row['email'] ?? '',
      $row['senha'] ?? '',
      isset($row['role_id']) ? (int) $row['role_id'] : null
    );
  }
}
