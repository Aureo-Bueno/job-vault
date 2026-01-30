<?php

namespace App\Util;

use App\Application\Service\RoleService;
use App\Infrastructure\Container\AppContainer;

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
  private static function service(): RoleService
  {
    return AppContainer::roleService();
  }

  /**
   * Check if user has a specific permission
   */
  public static function hasPermission($userId, $permissionName)
  {
    return self::service()->hasPermission((string) $userId, $permissionName);
  }

  /**
   * Get all permissions for a role
   */
  public static function getPermissionsByRole($roleId)
  {
    return self::service()->getPermissionsByRole((string) $roleId);
  }

  /**
   * Get user's role
   */
  public static function getUserRole($userId)
  {
    return self::service()->getUserRole((string) $userId);
  }

  /**
   * Check if user is admin
   */
  public static function isAdmin($userId)
  {
    return self::service()->isAdmin((string) $userId);
  }

  /**
   * Check if user is manager (gestor role)
   */
  public static function isManager($userId)
  {
    return self::service()->isManager((string) $userId);
  }

  /**
   * Require specific permission
   */
  public static function requirePermission($userId, $permissionName)
  {
    self::service()->requirePermission((string) $userId, $permissionName);
  }
}
