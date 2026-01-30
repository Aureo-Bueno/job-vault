<?php

namespace App\Entity;

use App\Db\Database;

/**
 * Role Entity
 *
 * Represents a role in the system
 *
 * @package App\Entity
 * @version 1.0
 */
class Role
{
  public $id;
  public $name;
  public $description;
  public $created_at;

  private $db;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->db = new Database('roles');
  }

  /**
   * Get role by ID
   *
   * @param int $id Role ID
   * @return Role|null Role object or null if not found
   */
  public static function getRoleById($id)
  {
    $db = new Database('roles');
    $result = $db->select("id = '{$id}'");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (empty($result)) {
      return null;
    }

    $role = new self();
    $roleData = $result[0];
    $role->id = $roleData->id;
    $role->name = $roleData->name;
    $role->description = $roleData->description;
    $role->created_at = $roleData->created_at;

    return $role;
  }

  /**
   * Get role by name
   *
   * @param string $name Role name
   * @return Role|null Role object or null if not found
   */
  public static function getRoleByName($name)
  {
    $db = new Database('roles');
    $result = $db->select("name = '{$name}'");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (empty($result)) {
      return null;
    }

    $role = new self();
    $roleData = $result[0];
    $role->id = $roleData->id;
    $role->name = $roleData->name;
    $role->description = $roleData->description;
    $role->created_at = $roleData->created_at;

    return $role;
  }

  /**
   * Get all roles
   *
   * @return array Array of Role objects
   */
  public static function getAllRoles()
  {
    $db = new Database('roles');
    $result = $db->select();

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    $roles = [];
    foreach ($result as $roleData) {
      $role = new self();
      $role->id = $roleData->id;
      $role->name = $roleData->name;
      $role->description = $roleData->description;
      $role->created_at = $roleData->created_at;
      $roles[] = $role;
    }

    return $roles;
  }

  /**
   * Get permissions for this role
   *
   * @return array Array of Permission objects
   */
  public function getPermissions()
  {
    if (!$this->id) {
      return [];
    }

    $db = new Database('role_permissions');
    $result = $db->execute(
      "SELECT p.* FROM permissions p
       JOIN role_permissions rp ON p.id = rp.permission_id
       WHERE rp.role_id = ?",
      [$this->id]
    );

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    $permissions = [];
    foreach ($result as $permData) {
      $perm = new Permission();
      $perm->id = $permData->id;
      $perm->name = $permData->name;
      $perm->description = $permData->description;
      $perm->module = $permData->module;
      $perm->action = $permData->action;
      $permissions[] = $perm;
    }

    return $permissions;
  }

  /**
   * Check if role has a specific permission
   *
   * @param string $permissionName Permission name
   * @return bool True if role has permission
   */
  public function hasPermission($permissionName)
  {
    if (!$this->id) {
      return false;
    }

    $db = new Database('role_permissions');
    $result = $db->execute(
      "SELECT 1 FROM role_permissions rp
       JOIN permissions p ON rp.permission_id = p.id
       WHERE rp.role_id = ? AND p.name = ?",
      [$this->id, $permissionName]
    );

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    return !empty($result);
  }

  /**
   * Create a new role
   *
   * @return bool Success status
   */
  public function create()
  {
    $this->db->insert([
      'name' => $this->name,
      'description' => $this->description
    ]);

    return true;
  }

  /**
   * Update role
   *
   * @return bool Success status
   */
  public function update()
  {
    if (!$this->id) {
      return false;
    }

    $this->db->update([
      'name' => $this->name,
      'description' => $this->description
    ], "id = '{$this->id}'");

    return true;
  }

  /**
   * Delete role
   *
   * @return bool Success status
   */
  public function delete()
  {
    if (!$this->id) {
      return false;
    }

    $this->db->delete("id = '{$this->id}'");
    return true;
  }
}
