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
class User
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
  public $name;

  /**
   * User email
   * @var string
   */
  public $email;

  /**
   * Password hash
   * @var string
   */
  public $password;

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
   * @return User|null User object or null if not found
   */
  public static function getUserByEmail($email)
  {
    try {
      $obDatabase = new Database('users');
      $rows = $obDatabase->select("email = '" . addslashes($email) . "'");

      // Convert PDOStatement to array if needed
      if (is_object($rows) && get_class($rows) === 'PDOStatement') {
        $rows = $rows->fetchAll(\PDO::FETCH_OBJ);
      }

      if (empty($rows)) {
        return null;
      }

      $row = $rows[0];
      $user = new self();
      $user->id = $row->id;
      $user->name = $row->name;
      $user->email = $row->email;
      $user->password = $row->password;
      $user->role_id = $row->role_id ?? null;

      return $user;
    } catch (\Exception $e) {
      error_log('User getUserByEmail error: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Get user by ID
   *
   * @param int $id User ID
   * @return User|null User object or null if not found
   */
  public static function getUserById($id)
  {
    try {
      $obDatabase = new Database('users');
      $rows = $obDatabase->select("id = '{$id}'");

      // Convert PDOStatement to array if needed
      if (is_object($rows) && get_class($rows) === 'PDOStatement') {
        $rows = $rows->fetchAll(\PDO::FETCH_OBJ);
      }

      if (empty($rows)) {
        return null;
      }

      $row = $rows[0];
      $user = new self();
      $user->id = $row->id;
      $user->name = $row->name;
      $user->email = $row->email;
      $user->password = $row->password;
      $user->role_id = $row->role_id ?? null;

      return $user;
    } catch (\Exception $e) {
      error_log('User getUserById error: ' . $e->getMessage());
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
  public function create()
  {
    try {
      $obDatabase = new Database('users');

      // Set default role if not set
      if (is_null($this->role_id)) {
        try {
          // Get 'usuario' role ID
          $dbRoles = new Database('roles');
          $roles = $dbRoles->select("name = 'usuario'");

          // Convert PDOStatement to array if needed
          if (is_object($roles) && get_class($roles) === 'PDOStatement') {
            $roles = $roles->fetchAll(\PDO::FETCH_OBJ);
          }

          $this->role_id = !empty($roles) ? $roles[0]->id : null;
        } catch (\Exception $e) {
          error_log('Failed to get default role: ' . $e->getMessage());
          $this->role_id = null;
        }
      }

      // Insert user
      $id = $obDatabase->insert([
        'name' => $this->name,
        'email' => $this->email,
        'password' => $this->password,
        'role_id' => $this->role_id
      ]);

      $this->id = $id;
      return $id;
    } catch (\Exception $e) {
      error_log('User create error: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Update user
   *
   * @return bool Success status
   */
  public function update()
  {
    try {
      $obDatabase = new Database('users');
      $obDatabase->update("id = '{$this->id}'", [
        'name' => $this->name,
        'email' => $this->email,
        'role_id' => $this->role_id
      ]);
      return true;
    } catch (\Exception $e) {
      error_log('User update error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Delete user
   *
   * @return bool Success status
   */
  public function delete()
  {
    try {
      $obDatabase = new Database('users');
      $obDatabase->delete("id = '{$this->id}'");
      return true;
    } catch (\Exception $e) {
      error_log('User delete error: ' . $e->getMessage());
      return false;
    }
  }
}
