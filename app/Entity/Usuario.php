<?php

namespace App\Entity;

use App\Db\Database;

/**
 * User Entity
 *
 * Represents a user in the system with role-based access control.
 *
 * @package App\Entity
 */
class Usuario
{
  /**
   * User ID
   * @var int
   */
  public $id;

  /**
   * User name
   * @var string
   */
  public $nome;

  /**
   * User email
   * @var string
   */
  public $email;

  /**
   * Password hash
   * @var string
   */
  public $senha;

  /**
   * Role ID (foreign key to roles table)
   * @var int|null
   */
  public $role_id;

  /**
   * Constructor
   *
   * Initialize user entity
   */
  public function __construct()
  {
    // Default role for new users
    $this->role_id = null;
  }

  /**
   * Get user by email
   *
   * @param string $email User email
   * @return Usuario|null User object or null if not found
   */
  public static function getUsuariosEmail($email)
  {
    try {
      $obDatabase = new Database('usuarios');
      $arrUsuarios = $obDatabase->select("email = '" . addslashes($email) . "'");

      // Convert PDOStatement to array if needed
      if (is_object($arrUsuarios) && get_class($arrUsuarios) === 'PDOStatement') {
        $arrUsuarios = $arrUsuarios->fetchAll(\PDO::FETCH_OBJ);
      }

      if (empty($arrUsuarios)) {
        return null;
      }

      $obData = $arrUsuarios[0];
      $obUsuario = new self();
      $obUsuario->id = $obData->id;
      $obUsuario->nome = $obData->nome;
      $obUsuario->email = $obData->email;
      $obUsuario->senha = $obData->senha;
      $obUsuario->role_id = $obData->role_id ?? null;

      return $obUsuario;
    } catch (\Exception $e) {
      error_log('Usuario getUsuariosEmail error: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Get usuario by ID
   *
   * @param int $id Usuario ID
   * @return Usuario|null Usuario object or null if not found
   */
  public static function getUsuarioById($id)
  {
    try {
      $obDatabase = new Database('usuarios');
      $arrUsuarios = $obDatabase->select("id = {$id}");

      // Convert PDOStatement to array if needed
      if (is_object($arrUsuarios) && get_class($arrUsuarios) === 'PDOStatement') {
        $arrUsuarios = $arrUsuarios->fetchAll(\PDO::FETCH_OBJ);
      }

      if (empty($arrUsuarios)) {
        return null;
      }

      $obData = $arrUsuarios[0];
      $obUsuario = new self();
      $obUsuario->id = $obData->id;
      $obUsuario->nome = $obData->nome;
      $obUsuario->email = $obData->email;
      $obUsuario->senha = $obData->senha;
      $obUsuario->role_id = $obData->role_id ?? null;

      return $obUsuario;
    } catch (\Exception $e) {
      error_log('Usuario getUsuarioById error: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Register new user
   *
   * Saves user to database and assigns default role
   *
   * @return int|null User ID or null on failure
   */
  public function cadastrar()
  {
    try {
      $obDatabase = new Database('usuarios');

      // Set default role if not set
      if (is_null($this->role_id)) {
        try {
          // Get 'usuario' role ID
          $dbRoles = new Database('roles');
          $roles = $dbRoles->select("nome = 'usuario'");

          // Convert PDOStatement to array if needed
          if (is_object($roles) && get_class($roles) === 'PDOStatement') {
            $roles = $roles->fetchAll(\PDO::FETCH_OBJ);
          }

          $this->role_id = !empty($roles) ? $roles[0]->id : null;
        } catch (\Exception $e) {
          error_log('Erro ao obter role padrão: ' . $e->getMessage());
          $this->role_id = null;
        }
      }

      // Insert user
      $id = $obDatabase->insert([
        'nome' => $this->nome,
        'email' => $this->email,
        'senha' => $this->senha,
        'role_id' => $this->role_id
      ]);

      $this->id = $id;
      return $id;
    } catch (\Exception $e) {
      error_log('Usuario cadastro error: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Update user
   *
   * @return bool Success status
   */
  public function atualizar()
  {
    try {
      $obDatabase = new Database('usuarios');
      $obDatabase->update("id = {$this->id}", [
        'nome' => $this->nome,
        'email' => $this->email,
        'role_id' => $this->role_id
      ]);
      return true;
    } catch (\Exception $e) {
      error_log('Usuario atualizar error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Delete user
   *
   * @return bool Success status
   */
  public function deletar()
  {
    try {
      $obDatabase = new Database('usuarios');
      $obDatabase->delete("id = {$this->id}");
      return true;
    } catch (\Exception $e) {
      error_log('Usuario deletar error: ' . $e->getMessage());
      return false;
    }
  }
}
