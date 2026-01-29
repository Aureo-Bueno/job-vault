<?php

namespace App\Util;

use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Usuario;

/**
 * Role and Permission Manager
 *
 * Uses Entity classes for role-based access control and permission checking.
 *
 * @package App\Util
 * @version 2.0
 */
class RoleManager
{
  /**
   * Check if user has a specific permission
   */
  public static function hasPermission($userId, $permissionName)
  {
    try {
      $usuario = Usuario::getUsuarioById($userId);

      if (!$usuario || !isset($usuario->role_id) || is_null($usuario->role_id)) {
        return false;
      }

      return RolePermission::roleHasPermission($usuario->role_id, $permissionName);
    } catch (\Exception $e) {
      error_log('RoleManager error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Get all permissions for a role
   */
  public static function getPermissionsByRole($roleId)
  {
    try {
      $permissions = RolePermission::getPermissionsByRoleId($roleId);

      $permissionNames = [];
      foreach ($permissions as $permission) {
        $permissionNames[] = $permission->nome;
      }

      return $permissionNames;
    } catch (\Exception $e) {
      error_log('RoleManager error: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Get user's role
   */
  public static function getUserRole($userId)
  {
    try {
      $usuario = Usuario::getUsuarioById($userId);

      if (!$usuario || !isset($usuario->role_id)) {
        return null;
      }

      return Role::getRoleById($usuario->role_id);
    } catch (\Exception $e) {
      error_log('RoleManager error: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Check if user is admin
   */
  public static function isAdmin($userId)
  {
    $role = self::getUserRole($userId);
    return $role && $role->nome === 'admin';
  }

  /**
   * Check if user is gestor
   */
  public static function isGestor($userId)
  {
    $role = self::getUserRole($userId);
    return $role && $role->nome === 'gestor';
  }

  /**
   * Require specific permission
   */
  public static function requirePermission($userId, $permissionName)
  {
    if (!self::hasPermission($userId, $permissionName)) {
      http_response_code(403);
      die('Você não tem permissão para acessar este recurso.');
    }
  }
}
