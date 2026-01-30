<?php

namespace App\Entity;

use App\Db\Database;

/**
 * RolePermission Entity
 *
 * Represents the relationship between roles and permissions
 *
 * @package App\Entity
 * @version 1.0
 */
class RolePermission
{
  public $id;
  public $role_id;
  public $permission_id;
  public $created_at;

  private $db;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->db = new Database('role_permissions');
  }

  /**
   * Get role permission by ID
   *
   * @param int $id RolePermission ID
   * @return RolePermission|null RolePermission object or null if not found
   */
  public static function getRolePermissionById($id)
  {
    $db = new Database('role_permissions');
    $result = $db->select("id = {$id}");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (empty($result)) {
      return null;
    }

    $rolePermission = new self();
    $rpData = $result[0];
    $rolePermission->id = $rpData->id;
    $rolePermission->role_id = $rpData->role_id;
    $rolePermission->permission_id = $rpData->permission_id;
    $rolePermission->created_at = $rpData->created_at;

    return $rolePermission;
  }

  /**
   * Get all permissions for a role
   *
   * @param int $roleId Role ID
   * @return array Array of Permission objects
   */
  public static function getPermissionsByRoleId($roleId)
  {
    $db = new Database('role_permissions');
    $result = $db->execute(
      "SELECT p.* FROM permissions p
       JOIN role_permissions rp ON p.id = rp.permission_id
       WHERE rp.role_id = ?",
      [$roleId]
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
   * Check if role has specific permission
   *
   * @param int $roleId Role ID
   * @param string $permissionName Permission name
   * @return bool True if role has permission
   */
  public static function roleHasPermission($roleId, $permissionName)
  {
    $db = new Database('role_permissions');
    $result = $db->execute(
      "SELECT 1 FROM role_permissions rp
       JOIN permissions p ON rp.permission_id = p.id
       WHERE rp.role_id = ? AND p.name = ?",
      [$roleId, $permissionName]
    );

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    return !empty($result);
  }

  /**
   * Get all roles for a permission
   *
   * @param int $permissionId Permission ID
   * @return array Array of Role objects
   */
  public static function getRolesByPermissionId($permissionId)
  {
    $db = new Database('role_permissions');
    $result = $db->execute(
      "SELECT r.* FROM roles r
       JOIN role_permissions rp ON r.id = rp.role_id
       WHERE rp.permission_id = ?",
      [$permissionId]
    );

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    $roles = [];
    foreach ($result as $roleData) {
      $role = new Role();
      $role->id = $roleData->id;
      $role->name = $roleData->name;
      $role->description = $roleData->description;
      $roles[] = $role;
    }

    return $roles;
  }

  /**
   * Assign permission to role
   *
   * @param int $roleId Role ID
   * @param int $permissionId Permission ID
   * @return bool Success status
   */
  public static function assignPermissionToRole($roleId, $permissionId)
  {
    $db = new Database('role_permissions');

    // Check if already assigned
    $result = $db->execute(
      "SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?",
      [$roleId, $permissionId]
    );

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (!empty($result)) {
      return false; // Already assigned
    }

    $db->insert([
      'role_id' => $roleId,
      'permission_id' => $permissionId
    ]);

    return true;
  }

  /**
   * Remove permission from role
   *
   * @param int $roleId Role ID
   * @param int $permissionId Permission ID
   * @return bool Success status
   */
  public static function removePermissionFromRole($roleId, $permissionId)
  {
    $db = new Database('role_permissions');
    $db->delete("role_id = '{$roleId}' AND permission_id = '{$permissionId}'");
    return true;
  }

  /**
   * Create role permission relationship
   *
   * @return bool Success status
   */
  public function create()
  {
    return self::assignPermissionToRole($this->role_id, $this->permission_id);
  }

  /**
   * Delete role permission relationship
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
