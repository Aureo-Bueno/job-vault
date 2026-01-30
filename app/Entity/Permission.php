<?php

namespace App\Entity;

use App\Db\Database;

/**
 * Permission Entity
 *
 * Represents a permission in the system
 *
 * @package App\Entity
 * @version 1.0
 */
class Permission
{
  public $id;
  public $name;
  public $description;
  public $module;
  public $action;
  public $created_at;

  private $db;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->db = new Database('permissions');
  }

  /**
   * Get permission by ID
   *
   * @param int $id Permission ID
   * @return Permission|null Permission object or null if not found
   */
  public static function getPermissionById($id)
  {
    $db = new Database('permissions');
    $result = $db->select("id = '{$id}'");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (empty($result)) {
      return null;
    }

    $permission = new self();
    $permData = $result[0];
    $permission->id = $permData->id;
    $permission->name = $permData->name;
    $permission->description = $permData->description;
    $permission->module = $permData->module;
    $permission->action = $permData->action;
    $permission->created_at = $permData->created_at;

    return $permission;
  }

  /**
   * Get permission by name
   *
   * @param string $name Permission name
   * @return Permission|null Permission object or null if not found
   */
  public static function getPermissionByName($name)
  {
    $db = new Database('permissions');
    $result = $db->select("name = '{$name}'");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    if (empty($result)) {
      return null;
    }

    $permission = new self();
    $permData = $result[0];
    $permission->id = $permData->id;
    $permission->name = $permData->name;
    $permission->description = $permData->description;
    $permission->module = $permData->module;
    $permission->action = $permData->action;
    $permission->created_at = $permData->created_at;

    return $permission;
  }

  /**
   * Get all permissions
   *
   * @return array Array of Permission objects
   */
  public static function getAllPermissions()
  {
    $db = new Database('permissions');
    $result = $db->select();

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    $permissions = [];
    foreach ($result as $permData) {
      $permission = new self();
      $permission->id = $permData->id;
      $permission->name = $permData->name;
      $permission->description = $permData->description;
      $permission->module = $permData->module;
      $permission->action = $permData->action;
      $permission->created_at = $permData->created_at;
      $permissions[] = $permission;
    }

    return $permissions;
  }

  /**
   * Get permissions by module
   *
   * @param string $module Module name
   * @return array Array of Permission objects
   */
  public static function getPermissionsByModule($module)
  {
    $db = new Database('permissions');
    $result = $db->select("module = '{$module}'");

    if (is_object($result) && get_class($result) === 'PDOStatement') {
      $result = $result->fetchAll(\PDO::FETCH_OBJ);
    }

    $permissions = [];
    foreach ($result as $permData) {
      $permission = new self();
      $permission->id = $permData->id;
      $permission->name = $permData->name;
      $permission->description = $permData->description;
      $permission->module = $permData->module;
      $permission->action = $permData->action;
      $permission->created_at = $permData->created_at;
      $permissions[] = $permission;
    }

    return $permissions;
  }

  /**
   * Create a new permission
   *
   * @return bool Success status
   */
  public function create()
  {
    $this->db->insert([
      'name' => $this->name,
      'description' => $this->description,
      'module' => $this->module,
      'action' => $this->action
    ]);

    return true;
  }

  /**
   * Update permission
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
      'description' => $this->description,
      'module' => $this->module,
      'action' => $this->action
    ], "id = '{$this->id}'");

    return true;
  }

  /**
   * Delete permission
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
